<?php

use SiLibrary\SiValidator2;

class FormBuilder
{
    private $fields = [];
    private $action;
    private $csrfToken;
    private $method = 'POST';
    private $errors = [];
    private $values = [];
    private $rules = [];
    private $requireConfirmation = false;
    private $callbacks = [];
    private $submit = [
        'label' => '送信',
        'attributes' => []
    ];
    private $confirmSubmit = [
        'label' => '送信',
        'attributes' => []
    ];
    private $confirmBack = [
        'label' => '戻る',
        'attributes' => []
    ];
    private $sessionKey;
    private $hasErrors = false;
    private $errorMessage = '';
    private $designType = 'default';

    public function __construct($formName, $action = '', $method = 'POST')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->action = htmlspecialchars($action);
        $this->method = strtoupper($method);
        $this->sessionKey = 'form_data_' . md5($formName);

        if ($_POST['SPIRAL_ACTION'] === 'Back') {
            $_SESSION[$this->sessionKey . '_confirmed'] = false;
            $_SESSION[$this->sessionKey . '_refreshed'] = true;
        }

        if (!isset($_POST['SPIRAL_ACTION']) && (!$_SESSION[$this->sessionKey . '_refreshed'] && !$_SESSION[$this->sessionKey . '_confirmed'])) {
            $this->resetSession();
        }

        $this->values = $_SESSION[$this->sessionKey . '_values'] ?? [];
        $this->errors = $_SESSION[$this->sessionKey . '_errors'] ?? [];
        $this->errorMessage = $_SESSION[$this->sessionKey . '_errorMessage'] ?? [];
        $this->hasErrors = !empty($this->errors); // エラーが存在するかどうかを設定
    }

    public function addField($type, $name, $label = '', $value = '', $attributes = [], $options = [])
    {
        $field = [
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'value' => $value,
            'attributes' => $attributes,
            'options' => $options,
        ];
        $this->fields[] = $field;
        return $this;
    }

    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }

    public function requireConfirmation($require)
    {
        $this->requireConfirmation = $require;
        return $this;
    }

    public function setSubmit($label, $attributes = [])
    {
        $this->submit = [
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    public function setConfirmSubmit($label, $attributes = [])
    {
        $this->confirmSubmit = [
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    public function setConfirmBack($label, $attributes = [])
    {
        $this->confirmBack = [
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    public function on($event, $callback)
    {
        $this->callbacks[$event] = $callback;
        return $this;
    }

    public function useDesignType($designType)
    {
        $this->designType = $designType;
        return $this;
    }


    public function render()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_method'] === $this->method) {
            if (!self::validateCsrfToken($_POST['csrf_token'])) {
                $this->errorMessage = 'CSRFトークンが無効です';
                $this->hasErrors = true;
                $_SESSION[$this->sessionKey . '_errors'] = $this->errors;
                $_SESSION[$this->sessionKey . '_errorMessage'] = $this->errorMessage;
                $_SESSION[$this->sessionKey . '_refreshed'] = true;
                echo '<meta http-equiv="refresh" content="0;URL=' . $this->action . '">';
                exit;
            }

            $action = $_POST['SPIRAL_ACTION'] ?? '';

            if ($action === 'Back') {
                $this->values = $_SESSION[$this->sessionKey . '_values'] ?? [];
                $this->renderForm();
                return;
            }

            if ($action === 'Confirm' && isset($_SESSION[$this->sessionKey . '_confirmed']) && $_SESSION[$this->sessionKey . '_confirmed'] === true) {
                $this->values = $_SESSION[$this->sessionKey . '_values'] ?? [];
                if ($this->validate($this->rules)) {
                    $this->renderRegistration();
                    $this->processRegistration();
                    $this->resetSession();
                } else {
                    $this->renderForm();
                }
                return;
            }

            $this->values = $this->getValues();
            $_SESSION[$this->sessionKey . '_values'] = $this->values;

            if ($this->validate($this->rules)) {
                if ($this->requireConfirmation) {
                    $_SESSION[$this->sessionKey . '_confirmed'] = true;
                    $this->renderConfirmationPage();
                    return;
                } else {
                    $this->renderRegistration();
                    $this->processRegistration();
                    $this->resetSession();
                    return;
                }
            } else {
                $_SESSION[$this->sessionKey . '_errors'] = $this->errors;
                $_SESSION[$this->sessionKey . '_errorMessage'] = $this->errorMessage;
                $_SESSION[$this->sessionKey . '_refreshed'] = true;
                echo '<meta http-equiv="refresh" content="0;URL=' . $this->action . '">';
                exit;
            }
        }

        $this->renderForm();
    }

    private function renderForm()
    {
        $this->csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $this->csrfToken;

        FieldRenderer::renderScript();
        FieldRenderer::renderFormStart($this->action, $this->csrfToken, $this->method, $this->designType);

        $values = $_SESSION[$this->sessionKey . '_values'] ?? [];
        $errors = $_SESSION[$this->sessionKey . '_errors'] ?? [];
        $errorMessage = $_SESSION[$this->sessionKey . '_errorMessage'] ?? '';

        if (isset($this->callbacks['inputform'])) {
            call_user_func($this->callbacks['inputform'], [$this, 'input'], $this->hasErrors);
        } else {
            $errorMessage && FieldRenderer::alertMessage($errorMessage, $this->designType);
            foreach ($this->fields as $field) {
                FieldRenderer::renderField($field, $values, $errors, $this->designType);
            }
        }

        FieldRenderer::renderSubmitButton($this->submit, $this->designType);
        FieldRenderer::renderFormEnd();

        unset($_SESSION[$this->sessionKey . '_errors']);
        unset($_SESSION[$this->sessionKey . '_errorMessage']);
        unset($_SESSION[$this->sessionKey . '_refreshed']);
    }

    private function renderConfirmationPage()
    {
        FieldRenderer::renderFormStart($this->action, $_SESSION['csrf_token'], $this->method, $this->designType);

        if (isset($this->callbacks['confirmform'])) {
            call_user_func($this->callbacks['confirmform'], [$this, 'input']);
        } else {
            foreach ($_SESSION[$this->sessionKey . '_values'] as $name => $value) {
                $field = array_values(array_filter($this->fields, fn($field) => $field['name'] === $name))[0];
                if (strpos($name, '_base64') !== false || strpos($name, '_mime') !== false || strpos($name, '_name') !== false) {
                    continue;
                }
                FieldRenderer::renderConfirmationField($field, $value, $this->designType);
            }
        }

        FieldRenderer::renderConfirmationButtons($this->confirmBack, $this->confirmSubmit, $this->designType);
        FieldRenderer::renderFormEnd();
    }

    private function renderRegistration()
    {
        if (isset($this->callbacks['thankyou'])) {
            call_user_func($this->callbacks['thankyou'], [$this, 'input']);
        } else {
            echo 'Form submitted and data registered successfully';
        }
    }

    private function processRegistration()
    {
        if (isset($this->callbacks['register'])) {
            call_user_func($this->callbacks['register'], $this->values);
        }
        $this->resetSession();
    }

    public function input($name)
    {
        $field = array_filter($this->fields, fn($field) => $field['name'] === $name);
        if (!$field) {
            return '';
        }
        $field = array_shift($field);
        $isError = isset($this->errors[$name]);


        ob_start();
        FieldRenderer::renderField($field, $this->values, $this->errors, $this->designType);
        $input_html = ob_get_clean();
        

        ob_start();
        FieldRenderer::renderConfirmationField($field, $this->values, $this->errors, $this->designType);
        $confirm_html = ob_get_clean();

        $inputObject = new stdClass();
        $inputObject->label = SanitizeHelper::sanitize($field['label']);
        $inputObject->name = SanitizeHelper::sanitize($field['name']);
        $inputObject->type = SanitizeHelper::sanitize($field['type']);
        $inputObject->message = SanitizeHelper::sanitize($isError ? $this->errors[$name] : '');
        $inputObject->value = SanitizeHelper::sanitize($this->values[$name] ?? $field['value']);
        $inputObject->attributes = array_map('SanitizeHelper::sanitize', $field['attributes']);
        $inputObject->default_input_html = $input_html;
        $inputObject->default_confirm_html = $confirm_html;

        return $inputObject;
    }

    public static function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && isset($token) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function validate($rules)
    {
        $labels = [];
        foreach ($this->fields as $field) {
            $labels[$field['name']] = $field['label'];
            if (array_key_exists($field['name'] . '_base64', $this->values)) {
                $labels[$field['name'] . '_base64'] = $field['label'];
            }
            if (array_key_exists($field['name'] . '_mime', $this->values)) {
                $labels[$field['name'] . '_mime'] = $field['label'];
            }
            if (array_key_exists($field['name'] . '_name', $this->values)) {
                $labels[$field['name'] . '_name'] = $field['label'];
            }
        }

        $results = SiValidator2::make($this->values, $rules, $labels);

        if ($results->isError()) {
            $errors = [];
            foreach ($results->getResults() as $error) {
                $errors[$error->getField()] = $error->message();
            }
            $this->errors = $errors;
            $this->hasErrors = true; // エラーステータスを設定
            $this->errorMessage = '入力エラーがあります';
            return false;
        }

        $this->errorMessage = '';
        $this->hasErrors = false; // エラーステータスを設定
        return true;
    }

    public function getValues()
    {
        $values = [];
        foreach ($this->fields as $field) {
            if (($field['type'] === 'file' || $field['type'] === 'image') && isset($_POST[$field['name'] . '_base64'])) {
                $values[$field['name']] = [
                    'base64' => $_POST[$field['name'] . '_base64'],
                    'mime' => $_POST[$field['name'] . '_mime'],
                    'name' => $_POST[$field['name'] . '_name'],
                ];
            } else if (isset($_POST[$field['name']])) {
                $values[$field['name']] = is_array($_POST[$field['name']])
                    ? $_POST[$field['name']]
                    : $_POST[$field['name']];
            } else {
                $values[$field['name']] = '';
            }
        }
        return $values;
    }

    private function resetSession()
    {
        unset($_SESSION[$this->sessionKey], $_SESSION[$this->sessionKey . '_confirmed'], $_SESSION[$this->sessionKey . '_errors'], $_SESSION[$this->sessionKey . '_errorMessage'], $_SESSION[$this->sessionKey . '_values'], $_SESSION[$this->sessionKey . '_refreshed']);
    }
}
?>
