<?php

namespace App\Jobs;

use App\Mail\EmailMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 100;
    private $email;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->email = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
     try {
        echo "Evento: EmailJob\n";

            $getjobid = $this->job->uuid(); //Pega o ID do Job

            //Mail::to($this->email['email_destinatario'])->send(new EmailMail($this->email)); // Envia o email

            //Verifica se existe um Job. Caso não existe ele seta o Status para 3 (erro)
            if (Mail::to($this->email['email_destinatario'])->send(new EmailMail($this->email))) {

                //Seta o uuid do JOB na tabela de email de acordo com os dados exatos.
                DB::table('email')->where('email_remetente', '=', $this->email['email_remetente'])
                                        ->where('email_destinatario', '=', $this->email['email_destinatario'])
                                        ->where('assunto', '=', $this->email['assunto'])
                                        ->update(['job' => $getjobid]);

                //Seta o status para 1 (enviado)
                DB::table('email')->where('job', '=', $this->job->uuid())
                                        ->update(['status' => '1']);

                //Logs
                echo "Email enviado para: {$this->email['email_destinatario']} \nJob ID: {$this->job->uuid()} \n";
                Log::debug('Email Enviado. UUID: ' . $this->job->uuid());


            } else {
                //Caso o JOB esteja vazio quer dizer que o email nao esta sendo enviado. Seta o valor para 3 (erro)
                $this->fail();
                DB::table('email')->where('email_remetente', '=', $this->email['email_remetente'])
                                        ->where('email_destinatario', '=', $this->email['email_destinatario'])
                                        ->where('assunto', '=', $this->email['assunto'])
                                        ->update(['status' => '3']);

                //Logs
                echo "[ElSE] Erro ao enviar email para: {$this->email['email_destinatario']} \nJob ID: {$this->job->uuid()} \n";
                Log::debug('[Else] Email NÃO Enviado');
            }

        } catch (\Exception $exception){
            $this->fail();
            DB::table('email')->where('job', '=', null)
                                    ->update(['status' => '3']);

            //Logs
            //echo $exception;
            echo "[EXCEP] Erro ao enviar email para: {$this->email['email_destinatario']} \nJob ID: {$this->job->uuid()} \n";
            Log::debug('[EXCEPTION] Email NÃO Enviado - Possiveis causas: Sistema de email indisponivel');
        }
    }
}
