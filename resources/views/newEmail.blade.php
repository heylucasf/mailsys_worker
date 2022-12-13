@component('mail::message')
    <h1>Olá, {{$email['email_destinatario']}}<br></h1>
    <h1>{{ $email['assunto'] }}</h1>
@endcomponent
