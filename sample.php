<?php

//<!-- SMP_DYNAMIC_PAGE DISPLAY_ERRORS=ON NAME=xxx -->

require 'FormGenerate/require.php';

// フォームの初期化
$form = new FormBuilder('example_form', '');

$form
     ->addField('email', 'email', 'Email')
     ->addField('password', 'password', 'パスワード' )
     ->setRules([
         'email' => ['required', 'email', (new SiLibrary\SiValidator2\Rules\UniqueRule('usersDb', 'email'))],
         'password' => [
                'required',
                'string',
                'min:8',             // 8文字以上
                'regex:/[A-Z]/',      // 少なくとも1つの大文字
                'regex:/[a-z]/',      // 少なくとも1つの小文字
                'regex:/[0-9]/',      // 少なくとも1つの数字
                'regex:/[@$!%*?&#]/', // 少なくとも1つの特殊文字
            ],
     ])
     ->requireConfirmation(true)
     ->useDesignType('bootstrap5')
     ->setSubmit('確認' , [ 'class' => 'btn btn-primary' ])
     ->setConfirmBack('戻る' , [ 'class' => 'btn' ])
     ->setConfirmSubmit('送信' , [ 'class' => 'btn btn-primary' ])
     ->on('register', function ($values) {
      SpiralDB::title('userDb')->create([
        'email' => $values['email'],
        'password' => $values['password'],
      ]);
     });
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  </head>
  <body>
<div class="container">
    <h1>Hello, world!</h1>
  <!-- Content here -->
<?php
// フォームのレンダリング
$form->render();
?>

</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
  </body>
</html>
