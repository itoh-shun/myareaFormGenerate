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

            echo "<script>
                function attachFileInputEvent(name, type) {
                var fileInput = document.getElementById(name);
                if (fileInput) {
                    fileInput.addEventListener('change', function() {
                        var file = this.files[0];
                        var reader = new FileReader();
                        reader.onloadend = function() {
                            var base64Data = reader.result.split(',')[1];
                            var mimeType = file.type;
                            var filename = file.name;
                            document.getElementById(name + '_base64').value = base64Data;
                            document.getElementById(name + '_mime').value = mimeType;
                            document.getElementById(name + '_name').value = filename;

                            if (type === 'image') {
                                document.getElementById(name + '_container').innerHTML = 
                                    '<div id=\"' + name + '_thumbnail\"><img src=\"data:' + mimeType + ';base64,' + base64Data + '\" alt=\"Uploaded Image\" style=\"max-width: 100px; max-height: 100px;\" /><button type=\"button\" id=\"' + name + '_clear\">Clear</button></div>';
                            } else {
                                document.getElementById(name + '_container').innerHTML = 
                                    '<div id=\"' + name + '_thumbnail\"><a href=\"data:' + mimeType + ';base64,' + base64Data + '\" download=\"' + filename + '\">' + filename + '</a><button type=\"button\" id=\"' + name + '_clear\">Clear</button></div>';
                            }
                            attachClearButtonEvent(name, type);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            }

            function attachClearButtonEvent(name, type) {
                var clearButton = document.getElementById(name + '_clear');
                if (clearButton) {
                    clearButton.addEventListener('click', function() {
                        document.getElementById(name + '_base64').value = '';
                        document.getElementById(name + '_mime').value = '';
                        document.getElementById(name + '_name').value = '';
                        document.getElementById(name + '_container').innerHTML = '<input type=\"file\" name=\"' + name + '\" id=\"' + name + '\">';
                        attachFileInputEvent(name, type);
                    });
                }
            }

        </script>";
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
                
                $value = '';

                if($values[$field['name']])
                {
                    $value = is_array($values[$field['name']]) ? array_map('htmlspecialchars', $values[$field['name']]) : htmlspecialchars($values[$field['name']]);
                } else {
                    $value = is_array($field['value']) ? array_map('htmlspecialchars', $field['value']) : htmlspecialchars($field['value']);
                }
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
                            $checked = in_array($optionValue, (array)$value) ? 'checked' : '';
                            echo "<input type=\"{$type}\" name=\"{$name}\" value=\"$optionValue\" $checked {$attributes}> $optionLabel<br>";
                        }
                        break;

                    case 'file':
                        echo "<div id=\"{$name}_container\">";
                        if ($value) {
                            echo "<div id=\"{$name}_thumbnail\">";
                            echo "<a href=\"data:{$values[$name.'_mime']};base64,{$value}\" download>{$this->values[$name . '_name']}</a>";
                            echo "<button type=\"button\" id=\"{$name}_clear\">Clear</button>";
                            echo "</div>";
                        } else {
                            echo "<input type=\"file\" name=\"{$name}\" id=\"{$name}\" {$attributes}>";
                        }
                        echo "</div>";
                        echo "<input type=\"hidden\" name=\"{$name}_base64\" id=\"{$name}_base64\" value=\"{$value}\">";
                        echo "<input type=\"hidden\" name=\"{$name}_mime\" id=\"{$name}_mime\" value=\"{$values[$name.'_mime']}\">";
                        echo "<input type=\"hidden\" name=\"{$name}_name\" id=\"{$name}_name\" value=\"{$values[$name.'_name']}\">";
                    
                        echo "<script>
                            attachFileInputEvent('{$name}', 'file');
                            attachClearButtonEvent('{$name}', 'file');
                        </script>";
                        break;

                    case 'image':
                        echo "<div id=\"{$name}_container\">";
                        if ($value) {
                            echo "<div id=\"{$name}_thumbnail\">";
                            echo "<img src=\"data:{$values[$name.'_mime']};base64,{$value}\" alt=\"Uploaded Image\" style=\"max-width: 100px; max-height: 100px;\" />";
                            echo "<button type=\"button\" id=\"{$name}_clear\">Clear</button>";
                            echo "</div>";
                        } else {
                            echo "<input type=\"file\" name=\"{$name}\" id=\"{$name}\" {$attributes}>";
                        }
                        echo "</div>";
                        echo "<input type=\"hidden\" name=\"{$name}_base64\" id=\"{$name}_base64\" value=\"{$value}\">";
                        echo "<input type=\"hidden\" name=\"{$name}_mime\" id=\"{$name}_mime\" value=\"{$values[$name.'_mime']}\">";
                        echo "<input type=\"hidden\" name=\"{$name}_name\" id=\"{$name}_name\" value=\"{$values[$name.'_name']}\">";
                    
                        echo "<script>
                            attachFileInputEvent('{$name}', 'image');
                            attachClearButtonEvent('{$name}', 'image');
                        </script>";
                        break;
                
                    default:
                        echo "<input type=\"{$type}\" name=\"{$name}\" value=\"$value\" {$attributes}\">";
                        break;
                }

                if (isset($errors[$name])) {
                    echo "<span style=\"color: red;\">{$errors[$name]}</span>";
                }
                
                if (isset($errors[$name . '_base64'])) {
                    echo "<span style=\"color: red;\">{$errors[$name . '_base64']}</span>";
                }
                
                if (isset($errors[$name . '_mime'])) {
                    echo "<span style=\"color: red;\">{$errors[$name . '_mime']}</span>";
                }
                
                if (isset($errors[$name . '_name'])) {
                    echo "<span style=\"color: red;\">{$errors[$name . '_name']}</span>";
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
                if (strpos($name, '_base64') !== false || strpos($name, '_mime') !== false || strpos($name, '_name') !== false) {
                    continue;
                }
                if (array_key_exists($name.'_base64', $_SESSION[$this->sessionKey . '_values'])){
                    $field = array_filter($this->fields, fn($field) => $field['name'] === $name);
                    if( $field['type'] === 'image' ){
                        $fileField = str_replace('_base64', '', $name);
                        $mimeType = $this->values[$fileField . '_mime'] ?? 'image/jpeg';
                        echo "<p><strong>{$fileField}:</strong> <img src=\"data:{$mimeType};base64,{$value}\" alt=\"Uploaded Image\" style=\"max-width: 100px; max-height: 100px;\" /></p>";
                    }
                    if( $field['type'] === 'file' ){
                        $fileField = str_replace('_base64', '', $name);
                        $mimeType = $this->values[$fileField . '_mime'] ?? 'application/octet-stream';
                        echo "<p><strong>{$fileField}:</strong> <a href=\"data:{$mimeType};base64,{$value}\" download>{$this->values[$fileField . '_name']}</a></p>";
                    }
                } else if (is_array($value)) {
                    echo "<p><strong>{$name}:</strong> " . htmlspecialchars(implode(', ', $value)) . "</p>";
                } else {
                    echo "<p><strong>{$name}:</strong> " . htmlspecialchars($value) . "</p>";
                }
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
            if(array_key_exists($field['name'] . '_base64', $this->values)){
                $labels[$field['name'] . '_base64'] = $field['label'];
            }
            if(array_key_exists($field['name'] . '_mime', $this->values)){
                $labels[$field['name'] . '_mime'] = $field['label'];
            }
            if(array_key_exists($field['name'] . '_name', $this->values)){
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
            return false;
        }

        $this->hasErrors = false; // エラーステータスを設定
        return true;
    }

    public function getValues()
    {
        $values = [];
        foreach ($this->fields as $field) {
            if ($field['type'] === 'file' && isset($_POST[$field['name'] . '_base64'])) {
                $values[$field['name']] = htmlspecialchars($_POST[$field['name'] . '_base64']);
                $values[$field['name'] . '_base64'] = htmlspecialchars($_POST[$field['name'] . '_base64']);
                $values[$field['name'] . '_mime'] = htmlspecialchars($_POST[$field['name'] . '_mime']);
                $values[$field['name'] . '_name'] = htmlspecialchars($_POST[$field['name'] . '_name']);
            } else if (isset($_POST[$field['name']])) {
                $values[$field['name']] = is_array($_POST[$field['name']])
                    ? array_map('htmlspecialchars', $_POST[$field['name']])
                    : htmlspecialchars($_POST[$field['name']]);
            } else {
                $values[$field['name']] = '';
            }
        }
        return $values;
    }

    private function resetSession()
    {
        unset($_SESSION[$this->sessionKey], $_SESSION[$this->sessionKey . '_confirmed'], $_SESSION[$this->sessionKey . '_errors'], $_SESSION[$this->sessionKey . '_values'], $_SESSION[$this->sessionKey . '_refreshed']);
    }
}
?>
