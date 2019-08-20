@component('mail::message')
# Hi there!

You have been invited to {{$space->name}} space of Khonik Space Messenger!

Please, click button below for verify your e-mail and join {{$space->name}} team.

@component('mail::button', ['url' => $user->email_verified_at?"http://$space->subdomain.chatclient.local:8080/#/":$user->email_verification_url,'color'=>'primary'])
Continue
@endcomponent

Thanks,<br>
Khonik Space Messenger
@endcomponent
