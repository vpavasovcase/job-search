<?php

namespace App\Services\Agents;

use App\Models\Application;
use App\Models\Communication;
use App\Models\User;
use App\Services\AnthropicClient;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class CommunicationAgent
{
    private Gmail $gmailService;
    private string $userEmail;

    public function __construct(
        private AnthropicClient $anthropic,
        private GoogleClient $googleClient
    ) {
        $this->initializeGmailService();
    }

    /**
     * Initialize Gmail service with OAuth credentials
     */
    private function initializeGmailService(): void
    {
        try {
            $this->googleClient->setApplicationName('Job Search Assistant');
            $this->googleClient->setScopes([Gmail::GMAIL_MODIFY]);
            $this->googleClient->setAuthConfig(storage_path('app/google/credentials.json'));
            $this->googleClient->setAccessType('offline');
            $this->googleClient->setPrompt('select_account consent');

            $this->gmailService = new Gmail($this->googleClient);
            $this->userEmail = $this->gmailService->users->getProfile('me')->getEmailAddress();
        } catch (\Throwable $e) {
            Log::error('Failed to initialize Gmail service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException('Failed to initialize Gmail service: ' . $e->getMessage());
        }
    }

    /**
     * Check inbox for new communications
     *
     * @return Collection<Communication>
     */
    public function checkInbox(): Collection
    {
        try {
            $messages = $this->gmailService->users_messages->listUsersMessages('me', [
                'q' => 'in:inbox -label:processed newer_than:2d',
                'maxResults' => 50
            ]);

            $communications = collect();

            foreach ($messages->getMessages() as $message) {
                $fullMessage = $this->gmailService->users_messages->get('me', $message->getId(), ['format' => 'full']);
                $headers = collect($fullMessage->getPayload()->getHeaders());

                $from = $headers->firstWhere('name', 'From')->getValue();
                $subject = $headers->firstWhere('name', 'Subject')->getValue();
                $content = $this->extractMessageContent($fullMessage->getPayload());

                // Analyze the email content using Claude
                $analysis = $this->analyzeEmail($subject, $content);

                if ($analysis['is_job_related']) {
                    $communication = $this->createCommunicationRecord(
                        $from,
                        $subject,
                        $content,
                        $analysis
                    );

                    // Label the message as processed
                    $this->gmailService->users_messages->modify('me', $message->getId(), [
                        'addLabelIds' => ['Label_processed']
                    ]);

                    $communications->push($communication);
                }
            }

            return $communications;
        } catch (\Throwable $e) {
            Log::error('Failed to check inbox', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException('Failed to check inbox: ' . $e->getMessage());
        }
    }

    /**
     * Send a follow-up communication for an application
     *
     * @param int $applicationId
     * @return Communication
     */
    public function sendFollowUp(int $applicationId): Communication
    {
        try {
            $application = Application::with(['job', 'user'])->findOrFail($applicationId);
            
            // Check if it's appropriate to send a follow-up
            if (!$this->shouldSendFollowUp($application)) {
                throw new RuntimeException('Follow-up not appropriate at this time');
            }

            // Generate follow-up content using Claude
            $followUpContent = $this->generateFollowUpContent($application);

            // Create email message
            $message = new Message();
            $rawEmail = $this->createEmail(
                $application->job->company_email ?? $this->findCompanyEmail($application),
                "Follow-up: {$application->job->title} Application",
                $followUpContent
            );
            $message->setRaw(base64_encode($rawEmail));

            // Send the email
            $this->gmailService->users_messages->send('me', $message);

            // Create communication record
            return Communication::create([
                'user_id' => $application->user_id,
                'job_id' => $application->job_id,
                'application_id' => $application->id,
                'type' => Communication::TYPE_EMAIL,
                'direction' => Communication::DIRECTION_OUTGOING,
                'content' => $followUpContent,
                'status' => Communication::STATUS_SENT,
                'sent_at' => now(),
                'metadata' => [
                    'follow_up_number' => $this->getFollowUpCount($application) + 1,
                    'previous_communication_date' => $this->getLastCommunicationDate($application),
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send follow-up', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RuntimeException('Failed to send follow-up: ' . $e->getMessage());
        }
    }

    /**
     * Send a notification to the user
     *
     * @param string $message
     * @return bool
     */
    public function notifyUser(string $message): bool
    {
        try {
            // Send email notification
            Mail::raw($message, function ($mail) {
                $mail->to($this->userEmail)
                    ->subject('Job Search Update')
                    ->priority(1);
            });

            // Log the notification
            Log::info('User notification sent', [
                'message' => $message,
                'email' => $this->userEmail
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send user notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Extract message content from Gmail payload
     *
     * @param \Google\Service\Gmail\MessagePart $payload
     * @return string
     */
    private function extractMessageContent($payload): string
    {
        if ($payload->getMimeType() === 'text/plain') {
            return base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        }

        $parts = $payload->getParts() ?? [];
        $content = '';

        foreach ($parts as $part) {
            if ($part->getMimeType() === 'text/plain') {
                $content .= base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
            }
        }

        return $content;
    }

    /**
     * Analyze email content using Claude
     *
     * @param string $subject
     * @param string $content
     * @return array
     */
    private function analyzeEmail(string $subject, string $content): array
    {
        $prompt = <<<PROMPT
        Analyze this email and determine if it's related to a job application or hiring process.
        Return a JSON object with your analysis.

        Subject: {$subject}
        Content: {$content}

        Analyze and return:
        {
            "is_job_related": boolean,
            "email_type": string (e.g., "interview_invitation", "application_received", "rejection", "follow_up_needed", "other"),
            "company_name": string|null,
            "position_title": string|null,
            "next_steps": string|null,
            "urgency_level": number (1-5),
            "suggested_response": string|null
        }
        PROMPT;

        $response = $this->anthropic->generateText($prompt, [
            'temperature' => 0.2,
            'max_tokens' => 500
        ]);

        return json_decode($response, true);
    }

    /**
     * Create a communication record from email
     *
     * @param string $from
     * @param string $subject
     * @param string $content
     * @param array $analysis
     * @return Communication
     */
    private function createCommunicationRecord(
        string $from,
        string $subject,
        string $content,
        array $analysis
    ): Communication {
        return Communication::create([
            'type' => Communication::TYPE_EMAIL,
            'direction' => Communication::DIRECTION_INCOMING,
            'content' => $content,
            'status' => Communication::STATUS_READ,
            'sent_at' => now(),
            'metadata' => [
                'from' => $from,
                'subject' => $subject,
                'analysis' => $analysis,
            ]
        ]);
    }

    /**
     * Check if a follow-up should be sent
     *
     * @param Application $application
     * @return bool
     */
    private function shouldSendFollowUp(Application $application): bool
    {
        $lastCommunication = $application->communications()
            ->latest('sent_at')
            ->first();

        if (!$lastCommunication) {
            return $application->submitted_at->diffInDays(now()) >= 5;
        }

        $followUpCount = $this->getFollowUpCount($application);
        if ($followUpCount >= 3) {
            return false;
        }

        $daysSinceLastCommunication = Carbon::parse($lastCommunication->sent_at)->diffInDays(now());
        return $daysSinceLastCommunication >= 7;
    }

    /**
     * Generate follow-up content using Claude
     *
     * @param Application $application
     * @return string
     */
    private function generateFollowUpContent(Application $application): string
    {
        $prompt = <<<PROMPT
        Generate a professional follow-up email for a job application.

        Context:
        - Position: {$application->job->title}
        - Company: {$application->job->company}
        - Application Date: {$application->submitted_at->format('F j, Y')}
        - Follow-up Number: {$this->getFollowUpCount($application) + 1}
        - Days Since Application: {$application->submitted_at->diffInDays(now())}

        The email should:
        1. Be professional and courteous
        2. Reference the specific position and application date
        3. Express continued interest
        4. Request an update on the application status
        5. Thank them for their time
        6. Include a professional signature
        PROMPT;

        return $this->anthropic->generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 500
        ]);
    }

    /**
     * Create email message in RFC 822 format
     *
     * @param string $to
     * @param string $subject
     * @param string $content
     * @return string
     */
    private function createEmail(string $to, string $subject, string $content): string
    {
        $boundary = md5(time());
        
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $this->userEmail,
            'To: ' . $to,
            'Subject: ' . $subject,
            'Content-Type: multipart/alternative; boundary=' . $boundary,
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" .
            '--' . $boundary . "\r\n" .
            'Content-Type: text/plain; charset=UTF-8' . "\r\n\r\n" .
            $content . "\r\n\r\n" .
            '--' . $boundary . '--';

        return $message;
    }

    /**
     * Get the number of follow-ups sent for an application
     *
     * @param Application $application
     * @return int
     */
    private function getFollowUpCount(Application $application): int
    {
        return $application->communications()
            ->where('direction', Communication::DIRECTION_OUTGOING)
            ->where('type', Communication::TYPE_EMAIL)
            ->whereJsonContains('metadata->is_follow_up', true)
            ->count();
    }

    /**
     * Get the date of the last communication
     *
     * @param Application $application
     * @return string|null
     */
    private function getLastCommunicationDate(Application $application): ?string
    {
        $lastComm = $application->communications()
            ->latest('sent_at')
            ->first();

        return $lastComm ? $lastComm->sent_at->format('Y-m-d H:i:s') : null;
    }

    /**
     * Find company email from job or application history
     *
     * @param Application $application
     * @return string
     * @throws RuntimeException if no email found
     */
    private function findCompanyEmail(Application $application): string
    {
        // Try to find email from previous communications
        $lastIncoming = $application->communications()
            ->where('direction', Communication::DIRECTION_INCOMING)
            ->where('type', Communication::TYPE_EMAIL)
            ->latest('sent_at')
            ->first();

        if ($lastIncoming && isset($lastIncoming->metadata['from'])) {
            return $lastIncoming->metadata['from'];
        }

        throw new RuntimeException('No company email found for follow-up');
    }
} 