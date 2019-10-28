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

### More classes will come.

## Author

Messenger library is brought to you by [Terry L.](https://terryl.in) from Taiwan.

## License

MIT

