<?php
namespace App\Console\Commands;

use App\Enums\SmsStatusEnum;
use App\Models\SmsLogs;
use App\Services\SimpleSmsService;
use Illuminate\Console\Command;

class SendSms extends Command
{
    protected $signature = 'sms:send
                            {phone : Número do destinatário (formato livre)}
                            {message? : Mensagem a ser enviada (se omitida, será solicitada interativamente)}';

    protected $description = 'Envia um SMS via Zoug e exibe o retorno do provider';

    public function handle(SimpleSmsService $smsService): int
    {
        $phone   = $this->argument('phone');
        $message = $this->argument('message') ?? $this->ask('Digite a mensagem SMS');

        if (empty(trim($message))) {
            $this->error('A mensagem não pode estar vazia.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Enviando SMS...');
        $this->comment("  Destinatário: {$phone}");
        $this->comment("  Mensagem:     {$message}");
        $this->newLine();

        $start = microtime(true);

        try {
            $result = $smsService->send($phone, $message);
        } catch (\Throwable $e) {
            SmsLogs::create([
                'recipient'     => $phone,
                'message'       => $message,
                'status'        => SmsStatusEnum::Failed,
                'error_message' => $e->getMessage(),
            ]);

            $this->newLine();
            $this->error('FALHA — exceção ao conectar no provider');
            $this->error("  {$e->getMessage()}");

            return self::FAILURE;
        }

        $elapsed = round((microtime(true) - $start) * 1000, 1);

        $statusLog = $result['sent'] ? SmsStatusEnum::Sent : SmsStatusEnum::Failed;

        SmsLogs::create([
            'recipient'     => $phone,
            'message'       => $message,
            'status'        => $statusLog,
            'error_message' => $result['error'],
            'sent_at'       => $result['sent'] ? now() : null,
        ]);

        $this->newLine();
        $this->line(str_repeat('-', 50));
        $this->line("  RETORNO DO PROVIDER ({$elapsed}ms)");
        $this->line(str_repeat('-', 50));

        if ($result['sent']) {
            $this->info("  Status:     SUCESSO");
            $this->info("  message_id: {$result['message_id']}");
        } else {
            $this->error("  Status: FALHA");
            $this->error("  Erro:   {$result['error']}");
        }

        $this->line(str_repeat('-', 50));
        $this->newLine();

        return $result['sent'] ? self::SUCCESS : self::FAILURE;
    }
}