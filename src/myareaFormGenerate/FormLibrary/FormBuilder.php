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
    private $submitLabel = '送信';
    private $confirmSubmitLabel = '送信';
    private $confirmBackLabel = '戻る';
    private $sessionKey;
    private $hasErrors = false;

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

        if(!isset($_POST['SPIRAL_ACTION']) && ( !$_SESSION[$this->sessionKey . '_refreshed'] && !$_SESSION[$this->sessionKey . '_confirmed'])){
            $this->resetSession();
        }

        $this->values = $_SESSION[$this->sessionKey . '_values'] ?? [];
        $this->errors = $_SESSION[$this->sessionKey . '_errors'] ?? [];
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
            'options' => $options
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

    public function setSubmitLabel($label)
    {
        $this->submitLabel = $label;
        return $this;
    }

    
    public function setConfirmSubmitLabel($label)
    {
        $this->confirmSubmitLabel = $label;
        return $this;
    }
    
    
    public function setConfirmBackLabel($label)
    {
        $this->confirmBackLabel = $label;
        return $this;
    }

    public function on($event, $callback)
    {
        $this->callbacks[$event] = $callback;
        return $this;
    }

    public function render()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_method'] === $this->method) {
            if (!self::validateCsrfToken($_POST['csrf_token'])) {
                die('CSRF token validation failed');
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
                    return;
                }
            } else {
                $_SESSION[$this->sessionKey . '_errors'] = $this->errors;
                $_SESSION[$this->sessionKey . '_refreshed'] = true;
                echo "<meta http-equiv=\"refresh\" content=\"0;URL={$this->action}\">";
                exit;
            }
        }

        $this->renderForm();
    }

    private function renderForm()
    {
        $this->csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $this->csrfToken;

        echo "<form action=\"{$this->action}\" method=\"POST\">";
        echo "<input type=\"hidden\" name=\"csrf_token\" value=\"{$this->csrfToken}\">";
        echo "<input type=\"hidden\" name=\"_method\" value=\"{$this->method}\">";

        $values = $_SESSION[$this->sessionKey . '_values'] ?? [];
        $errors = $_SESSION[$this->sessionKey . '_errors'] ?? [];

        if (isset($this->callbacks['inputform'])) {
            call_user_func($this->callbacks['inputform'], [$this, 'input'], $this->hasErrors);
        } else {
            foreach ($this->fields as $field) {
                $label = htmlspecialchars($field['label']);
                $name = htmlspecialchars($field['name']);
                $value = htmlspecialchars($values[$field['name']] ?? $field['value']);
                $type = htmlspecialchars($field['type']);

                $class = isset($errors[$name]) ? 'error' : '';
                echo "<div class=\"{$class}\">";
                if ($label) {
                    echo "<label for=\"{$name}\">{$label}</label>";
                }

                $attributes = '';
                foreach ($field['attributes'] as $key => $attrValue) {
                    $key = htmlspecialchars($key);
                    $attrValue = htmlspecialchars($attrValue);
                    $attributes .= " $key=\"$attrValue\"";
                }

                switch ($type) {
                    case 'textarea':
                        echo "<textarea name=\"{$name}\" {$attributes}>{$value}</textarea>";
                        break;

                    case 'select':
                        echo "<select name=\"{$name}\" {$attributes}>";
                        foreach ($field['options'] as $optionValue => $optionLabel) {
                            $optionValue = htmlspecialchars($optionValue);
                            $optionLabel = htmlspecialchars($optionLabel);
                            $selected = $value === $optionValue ? 'selected' : '';
                            echo "<option value=\"$optionValue\" $selected>$optionLabel</option>";
                        }
                        echo "</select>";
                        break;

                    case 'radio':
                    case 'checkbox':
                        foreach ($field['options'] as $optionValue => $optionLabel) {
                            $optionValue = htmlspecialchars($optionValue);
                            $optionLabel = htmlspecialchars($optionLabel);
                            $checked = $value === $optionValue ? 'checked' : '';
                            echo "<input type=\"{$type}\" name=\"{$name}\" value=\"$optionValue\" $checked {$attributes}> $optionLabel<br>";
                        }
                        break;

                    default:
                        echo "<input type=\"{$type}\" name=\"{$name}\" value=\"$value\" {$attributes}\">";
                        break;
                }

                if (isset($errors[$name])) {
                    echo "<span style=\"color: red;\">{$errors[$name]}</span>";
                }
                echo "</div>";
            }
        }

        echo "<button type=\"submit\" name='SPIRAL_ACTION' value='Next'>{$this->submitLabel}</button>";
        echo "</form>";

        unset($_SESSION[$this->sessionKey . '_errors']);
        unset($_SESSION[$this->sessionKey . '_refreshed']);
    }

    private function renderConfirmationPage()
    {
        if (isset($this->callbacks['confirmform'])) {
            call_user_func($this->callbacks['confirmform'], [$this, 'input']);
        } else {
            foreach ($_SESSION[$this->sessionKey . '_values'] as $name => $value) {
                echo "<p><strong>{$name}:</strong> " . htmlspecialchars(is_array($value) ? implode(', ', $value) : $value) . "</p>";
            }
        }
        echo '<form action="' . $this->action . '" method="POST">';
        echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
        echo "<input type=\"hidden\" name=\"_method\" value=\"{$this->method}\">";
        echo '<button type="submit" name="SPIRAL_ACTION" value="Back">'.$this->confirmBackLabel.'</button>';
        echo '<button type="submit" name="SPIRAL_ACTION" value="Confirm">'.$this->confirmSubmitLabel.'</button>';
        echo '</form>';
    }

    private function renderRegistration(){
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
        $value = htmlspecialchars($this->values[$name] ?? $field['value']);
        $isError = isset($this->errors[$name]);
        $message = $isError ? htmlspecialchars($this->errors[$name]) : '';
        return (object) [
            'value' => $value,
            'is_error' => $isError,
            'message' => $message
        ];
    }

    public static function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function validate($rules)
    {
        $labels = [];
        foreach ($this->fields as $field) {
            $labels[$field['name']] = $field['label'];
        }

        $results = SiValidator2::make($this->values, $rules, $labels);

        if ($results->isError()) {
            $errors = [];
            foreach ($results->getResults() as $error) {
                $errors[$error->getField()] = $error->message();
            }
            $this->errors = $errors;
            $this->hasErrors = true; // エラーステータスを設定
            return false;
        }

        $this->hasErrors = false; // エラーステータスを設定
        return true;
    }

    public function getValues()
    {
        $values = [];
        foreach ($this->fields as $field) {
            $values[$field['name']] = htmlspecialchars($_POST[$field['name']] ?? '');
        }
        return $values;
    }

    private function resetSession()
    {
        unset($_SESSION[$this->sessionKey], $_SESSION[$this->sessionKey . '_confirmed'], $_SESSION[$this->sessionKey . '_errors'], $_SESSION[$this->sessionKey . '_values'], $_SESSION[$this->sessionKey . '_refreshed']);
    }
}
?>
