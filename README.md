# フォームジェネレータ

このジェネレーターはSPIRALのフォーム同様の機能をPHPで構築するためのライブラリです。

## Zipファイルの作り方
cd src
zip -r ../archive.zip myareaFormGenerate/*

## 実装例
カスタムモジュールに、src 配下の myareaFormGenerate を設置します。
カスタムページに下記ソースコードを記述します。

~~~php
<?php
//<!-- SMP_DYNAMIC_PAGE DISPLAY_ERRORS=ON NAME=xxx -->
require 'myareaFormGenerate/require.php';

// フォームの初期化
$form = new FormBuilder('example_form');

$form->addField('text', 'username', 'ユーザー名')
     ->addField('password', 'password', 'パスワード')
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
     ->on('register', function ($values) {
        //登録する
         SpiralDB::title('users')->create([
          'name' => $values['username'],
          'email' => $values['email'],
         ]);
     });

// フォームのレンダリング
$form->render();
?>
~~~

## 使用方法

## フォームの初期化

~~~php
//<!-- SMP_DYNAMIC_PAGE DISPLAY_ERRORS=ON NAME=xxx -->
require 'myareaFormGenerate/require.php';
$form = new FormBuilder('example_form');
~~~

## フィールドの追加

以下の例では、テキストフィールド、パスワードフィールド、メールフィールド、テキストエリア、セレクトボックス、ラジオボタンをフォームに追加します。

~~~php
$form->addField('text', 'username', 'ユーザー名')
     ->addField('password', 'password', 'パスワード')
     ->addField('email', 'email', 'メールアドレス')
     ->addField('textarea', 'memo', 'メモ', 'Write about yourself...')
     ->addField('select', 'gender', '性別', '', [], [
         'male' => 'Male',
         'female' => 'Female',
         'other' => 'Other'
     ])
     ->addField('radio', 'subscribe', 'Subscribe to newsletter', '', [], [
         'yes' => 'Yes',
         'no' => 'No'
     ]);
~~~

## バリデーションルール一覧

## 基本ルール

### required

このフィールドは必須です。
~~~
例: 'username' => ['required']
~~~

### accepted

このフィールドは「yes」、「on」、「1」、「true」のいずれかである必要があります。
~~~
例: 'terms' => ['accepted']
~~~

### active_url

このフィールドは有効なURLである必要があります。
~~~
例: 'website' => ['active_url']
~~~

## 日付ルール

### after:date

このフィールドは指定された日付より後である必要があります。
~~~
例: 'start_date' => ['after:2022-01-01']
~~~

### after_or_equal:date

このフィールドは指定された日付と同じかそれより後である必要があります。
~~~
例: 'start_date' => ['after_or_equal:2022-01-01']
~~~

### before:date

このフィールドは指定された日付より前である必要があります。
~~~
例: 'end_date' => ['before:2023-01-01']
~~~

### before_or_equal:date

このフィールドは指定された日付と同じかそれより前である必要があります。
~~~
例: 'end_date' => ['before_or_equal:2023-01-01']
~~~

### date

このフィールドは有効な日付である必要があります。
~~~
例: 'birthday' => ['date']
~~~

### date_equals:date

このフィールドは指定された日付と同じである必要があります。
~~~
例: 'event_date' => ['date_equals:2023-06-15']
~~~

### date_format:format

このフィールドは指定された形式の日付である必要があります。
~~~
例: 'release_date' => ['date_format:Y-m-d']
~~~

## 文字列ルール

### alpha

このフィールドはアルファベットのみで構成される必要があります。
~~~
例: 'first_name' => ['alpha']
~~~

### alpha_dash

このフィールドはアルファベット、数字、ダッシュ、アンダースコアのみで構成される必要があります。
~~~
例: 'username' => ['alpha_dash']
~~~

### alpha_num

このフィールドはアルファベットと数字のみで構成される必要があります。
~~~
例: 'username' => ['alpha_num']
~~~

### between:min,max

このフィールドの値は指定された範囲内である必要があります。
~~~
例: 'age' => ['between:18,65']
~~~

### boolean

このフィールドは真偽値である必要があります。
~~~
例: 'is_active' => ['boolean']
~~~

### confirmed

このフィールドの値は対応する_confirmationフィールドの値と一致する必要があります。
~~~
例: 'password' => ['confirmed']
~~~

## 数値ルール

### digits:value

このフィールドは指定された桁数の数字である必要があります。
~~~
例: 'phone' => ['digits:10']
~~~

### digits_between:min,max

このフィールドは指定された範囲内の桁数の数字である必要があります。
~~~
例: 'zipcode' => ['digits_between:5,9']
~~~

### integer

このフィールドは整数である必要があります。
~~~
例: 'age' => ['integer']
~~~

### numeric

このフィールドは数値である必要があります。
~~~
例: 'price' => ['numeric']
~~~

### min:value

このフィールドの値は指定された最小値以上である必要があります。
~~~
例: 'age' => ['min:18']
~~~

### max:value

このフィールドの値は指定された最大値以下である必要があります。
~~~
例: 'age' => ['max:65']
~~~

### max_bytes:value

このフィールドのバイト数は指定された最大値以下である必要があります。
~~~
例: 'file_size' => ['max_bytes:1024']
~~~

## 特殊ルール

### email

このフィールドは有効なメールアドレス形式である必要があります。
~~~
例: 'email' => ['email']
~~~

### exclude_if:other,value

他のフィールドの値が指定された値と一致する場合、このフィールドを除外します。
~~~
例: 'reason' => ['exclude_if:status,approved']
~~~

### exclude_unless:other,value

他のフィールドの値が指定された値と一致しない場合、このフィールドを除外します。
~~~
例: 'comments' => ['exclude_unless:status,pending']
~~~

### exclude_without:other

他のフィールドが存在しない場合、このフィールドを除外します。
~~~
例: 'middle_name' => ['exclude_without:first_name']
~~~

### exists:table,column

このフィールドの値が指定されたテーブルとカラムに存在する必要があります。
~~~
例: 'email' => ['exists:users,email']
~~~

### unique:table,column

このフィールドの値が指定されたテーブルとカラムでユニークである必要があります。
~~~
例: 'username' => ['unique:users,username']
~~~

### timezone

このフィールドは有効なタイムゾーンである必要があります。
~~~
例: 'timezone' => ['timezone']
~~~

### string

このフィールドは文字列である必要があります。
~~~
例: 'bio' => ['string']
~~~

### regex:pattern

このフィールドは指定された正規表現パターンに一致する必要があります。
~~~
例: 'username' => ['regex:/^[A-Za-z0-9_]+$/']
~~~

### not_regex:pattern

このフィールドは指定された正規表現パターンに一致してはいけません。
~~~
例: 'username' => ['not_regex:/[^A-Za-z0-9_]/']
~~~

### json

このフィールドは有効なJSON文字列である必要があります。
~~~
例: 'settings' => ['json']
~~~
## バリデーションルールの設定

各フィールドに対するバリデーションルールを設定します。

利用可能なバリデーションは下記の通りです

~~~php
$form->setRules([
    'username' => ['required','min:3','max:255'],
    'password' => ['required','min:6'],
    'email' => ['required','email'],
    'memo' => ['max:500'],
    'gender' => ['required'],
    'subscribe' => ['required']
]);
~~~

### レコードの存在チェック
~~~php
$form->setRules([
    'username' => ['required','min:3','max:255'],
    'password' => ['required','min:6'],
    'email' => ['required','email',(new SiLibrary\SiValidator2\Rules\ExistsRule('usersDb', 'email'))],
    'memo' => ['max:500'],
    'gender' => ['required'],
    'subscribe' => ['required']
]);
~~~

~~~php
$form->setRules([
    'username' => ['required','min:3','max:255'],
    'password' => ['required','min:6'],
    'email' => ['required','email',(new SiLibrary\SiValidator2\Rules\ExistsRule('usersDb', 'email'))->where(function($query) use ($form) {
        return $query->where('status', $form->getValues()['status'] ?? 'default');
    })],
    'memo' => ['max:500'],
    'gender' => ['required'],
    'subscribe' => ['required']
]);
~~~


### レコードの重複チェック
~~~php
$form->setRules([
    'username' => ['required','min:3','max:255'],
    'password' => ['required','min:6'],
    'email' => ['required','email',(new SiLibrary\SiValidator2\Rules\UniqueRule('usersDb', 'email'))],
    'memo' => ['max:500'],
    'gender' => ['required'],
    'subscribe' => ['required']
]);
~~~

~~~php
$form->setRules([
    'username' => ['required','min:3','max:255'],
    'password' => ['required','min:6'],
    'email' => ['required','email',(new SiLibrary\SiValidator2\Rules\UniqueRule('usersDb', 'email'))->where(function($query) use ($form) {
        return $query->where('status', $form->getValues()['status'] ?? 'default');
    })],
    'memo' => ['max:500'],
    'gender' => ['required'],
    'subscribe' => ['required']
]);
~~~

## 確認ページの有効化
フォームに確認ページを挟むかどうかを設定します。

~~~php
$form->requireConfirmation(true);
~~~

## サブミットボタンのラベル設定

サブミットボタンのラベルを設定します。

~~~php
$form->setSubmitLabel('確認');
$form->setConfirmSubmitLabel('登録');
$form->setConfirmBackLabel('戻る');
~~~

## カスタムイベントの設定
inputform および confirmform イベントをカスタマイズできます。
この機能で取得される値はサニタイズがされたものになります。

~~~php
$form->on('inputform', function($input, $hasErrors) {
    ?>
    <?php if ($hasErrors): ?>
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
    ?>
    <h2>確認ページ</h2>
    <p><strong>Username:</strong> <?php echo $input('username')->value; ?></p>
    <p><strong>Email:</strong> <?php echo $input('email')->value; ?></p>
    <?php
})
->on('thankyou', function($input) {
    ?>
    <h2>完了ページ</h2>
    <p><strong>Username:</strong> <?php echo $input('username')->value; ?></p>
    <p><strong>Email:</strong> <?php echo $input('email')->value; ?></p>
    <?php
})
->on('register', function ($values) {
    // 登録処理
    // 例: データベースに保存
});
~~~

## フォームのレンダリング
最後に、フォームをレンダリングします。

~~~php
$form->render();
~~~

