# Messenger

Sending messages to the third-party API services made easy for PHP.

Shieldon Messenger is initially designed for a part of the Shieldon Firewall, sending notifications to webmasters or developers when their web applications are possibly under attacks. If you are looking for a web security library to protect your website, [Shieldon Firewall](https://github.com/terrylinooo/shieldon) might be a good choice for you.

## Installation

Use PHP Composer:

```php
composer require shieldon/messenger
```

Or, download it and include the Messenger autoloader.
```php
require 'Messenger/autoload.php';
```

## Usage

### Telegram

Open your Telegram App, add `BotFather` to start a new conversation. Type command `/newbot` to obtain your API key.

Remember, make sure your channel type is public. If you want to send messages to your private channel, googling will find solutions.

```php
$apiKey = 'your_api_key';
$channel = '@your_channel';

$telegram = new \Messenger\Telegram($apiKey, $channel);
$telegram->send('say something!');
```

### Line Notify

The access token can be obtained by clicking `Generate token` button at https://notify-bot.line.me/my/

Once you have obtained your developer access token for the chat group you choose, please invite `Line Notify` bot join your group, then the following code will work expectedly.

```php
$accessToken = 'your_access_token';

$line = new \Messenger\LineNotify($accessToken);
$line->send('say something!');
```

### SendGrid

If you have SendGrid API key, you can also send messages via SendGrid easily.

```php
$apiKey = 'your_api_key';

$sendgrid = new \Messenger\Sendgrid($apiKey);
$sendgrid->addSender('example.sender@gmail.com');
$sendgrid->addRecipient('example.recipient@gmail.com');
$sendgrid->setSubject('Foo, bar.')

$sendgrid->send('say something!');
```

### MailGun

```php
$apiKey = 'your_api_key';
$domain = 'your_domain_name';

$sendgrid = new \Messenger\Mailgun($apiKey, $domain);
$sendgrid->addSender('example.sender@gmail.com');
$sendgrid->addRecipient('example.recipient@gmail.com');
$sendgrid->setSubject('Foo, bar.')

$sendgrid->send('say something!');
```

### RocketChat

```php
$accessToken = 'your_auth_token';
$userId = 'your_user_id';
$serverUrl = 'https://your_rocket_chat.com:3000';
$channel = '#general';

$rocketChat = new \Messenger\RocketChat($accessToken, $userId, $serverUrl, $channel);
$rocketChat->send('say something!');
```

### Mail

Native PHP mail function.

```php
$mail = new \Messenger\Mail();
$mail->addSender('example.sender@gmail.com');
$mail->addRecipient('example.recipient@gmail.com');
$mail->setSubject('Foo, bar.')

$mail->send('say something!');
```

### Smtp

A very simple SMTP client.

```php

$user = 'email@your_domain.com';
$pass = '12345678';
$host = '127.0.0.1';
$port = '25';

$mail = new \Messenger\Smtp($user, $pass, $host, $port);

$mail->addSender('email@your_domain.com');
$mail->addRecipient('do-not-reply@gmail.com');
$mail->setSubject('Foo, bar.');

try {
    $mail->send('say something!');

    $result = $mail->printResult();
    
    // For debugging purpose.
    echo nl2br($result);

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

```

### Gmail

Extended from `Smtp`, a ready-to-use Gmail SMTP client.

#### Note: 

Google doesn't like people use their SMTP server to sending email by scripts, to make sure it can work without problems, you have to set the settings right:

- Check your Google Accounts -> Access for less secure apps -> Turn on
- Use your host where you use to send email with your Google account and confirm that you have trusted the device on.


```php

$user = 'your@gmail.com';
$pass = 'your_password';

$gmail = new \Messenger\Gmail($user, $pass);

$gmail->addSender('your@gmail.com');
$gmail->addRecipient('test@gmail.com');
$gmail->setSubject('Foo, bar.');

try {
    $mail->send('say something!');

    $result = $mail->printResult();
    
    // For debugging purpose.
    echo nl2br($result);

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}
```

## Author

Messenger library is brought to you by [Terry L.](https://terryl.in) from Taiwan.

## License

MIT

