# Messenger

Sending messages to the third-party services made easy for PHP.

*Supported modules:*

- Telegram
- Line Notify
- Rocket Chat
- Slack
- Slack Webhook
- Mail
- SMTP
- SendGrid
- MailGun
- MailGun (SMTP)
- Gmail (SMTP)
- Yahoo (SMTP)
- Outlook (SMTP)

More modules will come in the future...

## Installation

Use PHP Composer:

```php
composer require shieldon/messenger
```

Or, download it and include the Messenger autoloader.
```php
require './autoload.php';
```

## Basic Usage

Public API methods:

- send
- debugMode
- printResult

Other than the Mailer classes, the only one public API is `send()`. The only thing you need to do is to assign the required data fields into constructor when initializing instance.

### Telegram

Open your Telegram App, add `BotFather` to start a new conversation. Type command `/newbot` to obtain your API key.

Remember, make sure your channel type is public. If you want to send messages to your private channel, googling will find solutions.

```php
$apiKey = 'your_api_key';
$channel = '@your_channel';

$telegram = new \Shieldon\Messenger\Telegram($apiKey, $channel);

if ($telegram->send('say something!')) {
    echo 'Message has been sent to your Telegram group.';
} else {
    echo 'Failed to send message.' . "\n";
    echo $telegram->printResult();
}
```

### Line Notify

The access token can be obtained by clicking `Generate token` button at this [signup page](https://notify-bot.line.me/my/).

Once you have obtained your developer access token for the chat group you choose, invite `Line Notify` bot join your Line group, then the following code will work as expected.

```php
$accessToken = 'your_access_token';

$line = new \Shieldon\Messenger\LineNotify($accessToken);

if ($line->send('say something!')) {
    echo 'Message has been sent to your Line group.';
} else {
    echo 'Failed to send message.' . "\n";
    echo $line->printResult();
}
```

### RocketChat

```php
$accessToken = 'your_auth_token';
$userId = 'your_user_id';
$serverUrl = 'https://your_rocket_chat.com:3000';
$channel = '#general';

$rocketChat = new \Shieldon\Messenger\RocketChat($accessToken, $userId, $serverUrl, $channel);

if ($rocketChat->send('say something!')) {
    echo 'Message has been sent to your RocketChat channel.';
} else {
    echo 'Failed to send message.' . "\n";
    echo $rocketChat->printResult();
}
```

### Slack

Please clearfully read Slack's official [API docs](https://api.slack.com/messaging/sending) to find out things you need.

Guide:

- Create a App
- Assign `channels:read` and `chat:write:bot` permissions to your App.
- Assign your APP to your workspace.
- Obtain bot's access token.
- Add your App to the channel you would like to send messages.

```php
$botToken = 'xoxb-551837935968-920623655894-TI1zWtaDLCkTzZaFFuyfzL56';
$channel = '#general';

$slack = new \Shieldon\Messenger\Slack($botToken, $channel);

if ($slack->send('say something!')) {
    echo 'Message has been sent to your Slack channel.';
} else {
    echo 'Failed to send message.' . "\n";
    echo $slack->printResult();
}
```

### Slack Webhook

This would be the simplest way for messaging. Please clearfully read Slack's official [API docs](https://api.slack.com/messaging/webhooks) to find out things you need.

```php
$webhook = 'https://hooks.slack.com/services/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';

$slack = new \Shieldon\Messenger\SlackWebhook($webhook);

if ($slack->send('say something!')) {
    echo 'Message has been sent to your Slack channel.';
} else {
    echo 'Failed to send message.' . "\n";
    echo $slack->printResult();
}

```

ok.

---

## Mailer Usage

Public API methods:

- send
- addTo
- addCc
- addBcc
- addReplyTo
- addRecipient
- setRecipients
- setSubject
- setSender
- debugMode
- printResult

There is no need to metion content type when using Mailer, the content type is automatically detected.

### Mail

Native PHP mail function. To use this class, be sure you have set the settings right in your `php.ini`.

```php
$mail = new \Shieldon\Messenger\Mail();
$mail->addSender('example.sender@gmail.com');
$mail->addRecipient('example.recipient@gmail.com');
$mail->setSubject('Foo, bar.');

if ($mail->send('say something!')) {
    echo 'Email has been delivered via PHP\'s native mail function.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $maingun->printResult();
}
```

### SMTP

A very simple SMTP client.

```php

$user = 'email@your_domain.com';
$pass = '12345678';
$host = '127.0.0.1';
$port = '25';

$mail = new \Shieldon\Messenger\Smtp($user, $pass, $host, $port);

$mail->addSender('email@your_domain.com');
$mail->addRecipient('do-not-reply@gmail.com');
$mail->setSubject('Foo, bar.');

if ($mail->send('say something!')) {
    echo 'Email has been delivered via SMTP.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $maingun->printResult();
}
```

Note:

If you would like to use **SMTPS** or **STARTTLS**, the `$host` should have a prefix. 

For example:

```php
$host = 'ssl://smtp.gmail.com'; // SMTPS
$host = 'tls://smtp.gmail.com'; // STARTTLS
```

### SendGrid

If you have SendGrid API key, you can also send messages via SendGrid easily.

```php
$apiKey = 'your_api_key';

$sendgrid = new \Shieldon\Messenger\Sendgrid($apiKey);
$sendgrid->addSender('example.sender@gmail.com');
$sendgrid->addRecipient('example.recipient@gmail.com');
$sendgrid->setSubject('Foo, bar.');

if ($sendgrid->send('say something!')) {
    echo 'Email has been delivered via SendGrid API.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $maingun->printResult();
}
```

### MailGun

```php
$apiKey = 'your_api_key';
$domain = 'your_domain_name';

$maingun = new \Shieldon\Messenger\Mailgun($apiKey, $domain);
$maingun->addSender('example.sender@gmail.com');
$maingun->addRecipient('example.recipient@gmail.com');
$maingun->setSubject('Foo, bar.');

if ($maingun->send('say something!')) {
    echo 'Email has been delivered via MailGun API.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $maingun->printResult();
}
```

### MailGun SMTP

Extended from `Smtp`, a ready-to-use MailGun SMTP client.

```php
$user = 'your@gmail.com';
$pass = 'your_password';

$maingun = new \Shieldon\Messenger\Smtp\Mailgun($user, $pass);

$maingun->addSender('example.sender@gmail.com');
$maingun->addRecipient('example.recipient@gmail.com');
$maingun->setSubject('Foo, bar.');

if ($maingun->send('say something!')) {
    echo 'Email has been delivered via MainGun SMTP server.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $maingun->printResult();
}
```

### Gmail

Extended from `Smtp`, a ready-to-use Gmail SMTP client.

```php
$user = 'your@gmail.com';
$pass = 'your_password';

$gmail = new \Shieldon\Messenger\Smtp\Gmail($user, $pass);

$gmail->addSender('your@gmail.com');
$gmail->addRecipient('test@gmail.com');
$gmail->setSubject('Foo, bar.');

if ($gmail->send('say something!')) {
    echo 'Email has been delivered via Gmail SMTP server.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $gmail->printResult();
}
```

Note:

Google doesn't like people use their SMTP server to sending email by scripts, to make sure it can work without problems, you have to set the settings right:

- Check your Google Accounts -> Access for less secure apps -> Turn on
- Use your host where you use to send email with your Google account and confirm that you have trusted the device on.

### Yahoo Mail

Extended from `Smtp`, a ready-to-use Yahoo SMTP client.


```php
$user = 'your@yahoo.com';
$pass = 'your_password';

$yahooMail = new \Shieldon\Messenger\Smtp\Yahoo($user, $pass);

$yahooMail->addSender('your@yahoo.com');
$yahooMail->addRecipient('test@gmail.com');
$yahooMail->setSubject('Foo, bar.');

if ($yahooMail->send('say something!')) {
    echo 'Email has been delivered via Yahoo SMTP server.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $yahooMail->printResult();
}
```

Note: You can use your account password but if you are facing the following error:

```
(#AUTH005) Too many bad auth attempts error when trying to send email.
```

That is because that Yahoo might not allow 3rd-party products access the SMTP server by default. To resolve this problem:

- Go to [Account Security](https://login.yahoo.com/account/security), 
- Under **Manage App password** section, create a password for that App.
- Use your App password instead of your account password.


### Outlook Mail (Office365)

Extended from `Smtp`, a ready-to-use Yahoo SMTP client.


```php
$user = 'your@outlook.com';
$pass = 'your_password';

$outlook = new \Shieldon\Messenger\Smtp\Outlook($user, $pass);

$outlook->addSender('your@outlook.com');
$outlook->addRecipient('test@gmail.com');
$outlook->setSubject('Foo, bar.');

if ($outlook->send('say something!')) {
    echo 'Email has been delivered via Office365 SMTP server.';
} else {
    echo 'Failed to send email.' . "\n";
    echo $yahooMail->printResult();
}
```

Note:

When sending email via Office365 SMTP server at the first time, you will receive a notification email from *Outlook.com Team* to confirm your activity.

![](https://i.imgur.com/F4YlKkg.png)

Once you have completed the validation, you will be able to send email via Office365 SMTP server.


---

## Debug

### debugMode()

If you would like to catch exceptions, you use turn `debugMode` on. This option will throw exceptions when error occurred.

For example:

```php
$mail = new \Shieldon\Messenger\Smtp($user, $pass, $host, $port);

$mail->debugMode(true);

$mail->addSender('email@your_domain.com');
$mail->addRecipient('do-not-reply@gmail.com');
$mail->setSubject('Foo, bar.');

try {
    $mail->send('say something!');

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

```

### printResult()

If you would like to print the executed results, you can use `printResult()`.

For example:

```php
$mail = new \Shieldon\Messenger\Smtp($user, $pass, $host, $port);
$mail->addSender('email@your_domain.com');
$mail->addRecipient('do-not-reply@gmail.com');
$mail->setSubject('Foo, bar.');
$mail->send('say something!');

echo $mail->printResult();
```

If the email is sent successfully, the result will look like the text below:

```
success: true
message: Email is sent.
--- result ---
connection: 220 smtp.gmail.com ESMTP x11sm6715821pfn.53 - gsmtp
hello: 250 smtp.gmail.com at your service
auth_type: 334 VXNlcm5hbWU6
user: 334 UGFzc3dvcmQ6
pass: 235 2.7.0 Accepted
from: 250 2.1.0 OK x11sm6715821pfn.53 - gsmtp
to: 250 2.1.5 OK x11sm6715821pfn.53 - gsmtp
cc: 250 2.1.5 OK x11sm6715821pfn.53 - gsmtp
bcc: 250 2.1.5 OK x11sm6715821pfn.53 - gsmtp
data: 354 Go ahead x11sm6715821pfn.53 - gsmtp
send: 250 2.0.0 OK 1579887885 x11sm6715821pfn.53 - gsmtp
quit: 221 2.0.0 closing connection x11sm6715821pfn.53 - gsmtp
```

---

## Author

Messenger library is brought to you by [Terry L.](https://terryl.in) from Taiwan.

Shieldon Messenger is initially designed for a part of the [Shieldon Firewall](https://github.com/terrylinooo/shieldon), sending notifications to webmasters or developers when their web applications are possibly under attacks. If you are looking for a web security library to protect your website, Shieldon [Firewall](https://shieldon.io) might be a good choice for you.

## License

MIT

