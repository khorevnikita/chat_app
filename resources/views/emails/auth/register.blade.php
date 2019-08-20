@component('mail::message')
# Hi there!

You have been registered in Khonik Space Messenger!

Please, click button below for verify your e-mail.


@component('mail::button', ['url' => $user->email_verification_url,'color'=>'primary'])
    Continue
@endcomponent

Thanks,<br>
Khonik Space Messenger
@endcomponent
