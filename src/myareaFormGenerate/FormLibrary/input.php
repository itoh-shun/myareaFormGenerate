<?php

//<!-- SMP_DYNAMIC_PAGE DISPLAY_ERRORS=ON NAME=xxx -->

session_start();
require 'myareaFormGenerate/require.php';

// フォームの初期化
$form = new FormBuilder('example_form', '');

$form->addField('text', 'username', 'Username')
     ->addField('password', 'password', 'Password')
     ->addField('email', 'email', 'Email')
     ->addField('textarea', 'bio', 'Bio', 'Write about yourself...')
     ->addField('select', 'gender', 'Gender', '', [], [
         'male' => 'Male',
         'female' => 'Female',
         'other' => 'Other'
     ])
     ->addField('radio', 'subscribe', 'Subscribe to newsletter', '', [], [
         'yes' => 'Yes',
         'no' => 'No'
     ])
     ->setRules([
         'username' => ['required','min:3','max:255'],
         'password' => ['required','min:6'],
         'email' => ['required','email'],
         'bio' => ['max:500'],
         'gender' => ['required'],
         'subscribe' => ['required']
     ])
     ->requireConfirmation(false)
     ->setSubmitLabel('登録')
     ->on('inputform', function($input, $hasErrors) {
        // 登録ページ
         if ($hasErrors): ?>
             <div style="color: red;">There are errors in the form. Please fix them.</div>
         <?php endif; ?>
         <div>
             <label for="username">Username:</label>
             <input type="text" name="username" value="<?php echo $input('username')->value; ?>">
             <?php if ($input('username')->is_error): ?>
                 <span style="color: red;"><?php echo $input('username')->message; ?></span>
             <?php endif; ?>
         </div>
         <div>
             <label for="email">Email:</label>
             <input type="email" name="email" value="<?php echo $input('email')->value; ?>">
             <?php if ($input('email')->is_error): ?>
                 <span style="color: red;"><?php echo $input('email')->message; ?></span>
             <?php endif; ?>
         </div>
         <?php
     })
     ->on('confirmform', function($input) {
         // 確認ページ
     })
     ->on('thankyou', function ($values) {
         // 完了ページ
     })
     ->on('register', function ($values) {
         // 登録処理
         // 例: データベースに保存
     });

// フォームのレンダリング
$form->render();
?>
