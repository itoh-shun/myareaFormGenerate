<?php

class Bootstrap5Renderer
{
    public function renderField($field, $values, $errors)
    {
        $label = SanitizeHelper::sanitize($field['label']);
        $name = SanitizeHelper::sanitize($field['name']);
        
        $value = '';
        $id = uniqid();
        
        if ($values[$field['name']]) {
            $value = is_array($values[$field['name']]) ? array_map([SanitizeHelper::class, 'sanitize'], $values[$field['name']]) : SanitizeHelper::sanitize($values[$field['name']]);
        } else {
            $value = is_array($field['value']) ? array_map([SanitizeHelper::class, 'sanitize'], $field['value']) : SanitizeHelper::sanitize($field['value']);
        }

        if (is_array($value) && isset($value['base64']) && isset($value['mime']) && isset($value['name'])) {
            $base64Value = SanitizeHelper::sanitize($value['base64']);
            $mimeTypeValue = SanitizeHelper::sanitize($value['mime']);
            $filenameValue = SanitizeHelper::sanitize($value['name']);
        } else {
            $base64Value = '';
            $mimeTypeValue = '';
            $filenameValue = '';
        }

        $type = SanitizeHelper::sanitize($field['type']);

        $class = isset($errors[$name]) ? 'form-field error' : 'form-field ';
        echo '<div class="' . $class . ' row mb-3">';
        if ($label) {
            echo '<label for="' . $name . '_' . $id . '" class="col-sm-2 col-form-label">' . $label . '</label>';
        }

        $attributes = '';
        foreach ($field['attributes'] as $key => $attrValue) {
            $key = SanitizeHelper::sanitize($key);
            $attrValue = SanitizeHelper::sanitize($attrValue);
            $attributes .= ' ' . $key . '="' . $attrValue . '"';
        }

        echo '<div class="col-sm-10">';

        switch ($type) {
            case 'textarea':
                echo '<textarea id="' . $name . '_' . $id . '" name="' . $name . '"' . $attributes . ' class="form-control">' . $value . '</textarea>';
                break;

            case 'select':
                echo '<select name="' . $name . '" id="' . $name . '_' . $id . '"' . $attributes . ' class="form-control">';
                foreach ($field['options'] as $optionValue => $optionLabel) {
                    $optionValue = SanitizeHelper::sanitize($optionValue);
                    $optionLabel = SanitizeHelper::sanitize($optionLabel);
                    $selected = $value === $optionValue ? 'selected' : '';
                    echo '<option value="' . $optionValue . '" ' . $selected . '>' . $optionLabel . '</option>';
                }
                echo '</select>';
                break;

            case 'radio':
            case 'checkbox':
                foreach ($field['options'] as $optionValue => $optionLabel) {
                    $optionValue = SanitizeHelper::sanitize($optionValue);
                    $optionLabel = SanitizeHelper::sanitize($optionLabel);
                    $checked = in_array($optionValue, (array)$value) ? 'checked' : '';
                    echo '<input type="' . $type . '" name="' . $name . '" value="' . $optionValue . '" ' . $checked . $attributes . ' class="form-check-input"> ' . $optionLabel . '<br>';
                }
                break;

            case 'file':
                echo '<div id="' . $name . '_container">';
                if ($base64Value) {
                    echo '<div id="' . $name . '_thumbnail">';
                    echo '<a href="data:' . $mimeTypeValue  . ';base64,' . $base64Value . '" download>' . $filenameValue . '</a>';
                    echo '<button type="button" id="' . $name . '_clear" class="btn btn-secondary btn-sm mx-2">Clear</button>';
                    echo '</div>';
                } else {
                    echo '<input type="file" class="form-control" name="' . $name . '" id="' . $name . '" ' . $attributes . '>';
                }
                echo '</div>';
                echo '<input type="hidden" name="' . $name . '_base64" id="' . $name . '_base64" value="' .$base64Value . '">';
                echo '<input type="hidden" name="' . $name . '_mime" id="' . $name . '_mime" value="' . $mimeTypeValue . '">';
                echo '<input type="hidden" name="' . $name . '_name" id="' . $name . '_name" value="' . $filenameValue. '">';
            
                echo '<script>
                    attachFileInputEvent("' . $name . '", "file");
                    attachClearButtonEvent("' . $name . '", "file");
                </script>';
                break;

            case 'image':
                echo '<div id="' . $name . '_container">';
                if ($base64Value) {
                    echo '<div id="' . $name . '_thumbnail">';
                    echo '<img src="data:' . $mimeTypeValue  . ';base64,' . $base64Value. '" alt="Uploaded Image" style="max-width: 100px; max-height: 100px;" />';
                    echo '<button type="button" id="' . $name . '_clear" class="btn btn-secondary btn-sm mx-2">Clear</button>';
                    echo '</div>';
                } else {
                    echo '<input type="file" class="form-control" name="' . $name . '" id="' . $name . '" ' . $attributes . '>';
                }
                echo '</div>';
                echo '<input type="hidden" name="' . $name . '_base64" id="' . $name . '_base64" value="' .$base64Value . '">';
                echo '<input type="hidden" name="' . $name . '_mime" id="' . $name . '_mime" value="' . $mimeTypeValue . '">';
                echo '<input type="hidden" name="' . $name . '_name" id="' . $name . '_name" value="' . $filenameValue . '">';
            
                echo '<script>
                    attachFileInputEvent("' . $name . '", "image");
                    attachClearButtonEvent("' . $name . '", "image");
                </script>';
                break;
              
        
            default:
                echo '<input type="' . $type . '" id="' . $name . '_' . $id . '" name="' . $name . '" value="' . SanitizeHelper::sanitize($value) . '"' . $attributes . ' class="form-control">';
                break;
        }

        if (isset($errors[$name])) {
            echo '<span class="text-danger">' . SanitizeHelper::sanitize($errors[$name]) . '</span>';
        }
        
        if (isset($errors[$name . '_base64'])) {
            echo '<span class="text-danger">' . SanitizeHelper::sanitize($errors[$name . '_base64']) . '</span>';
        }
        
        if (isset($errors[$name . '_mime'])) {
            echo '<span class="text-danger">' . SanitizeHelper::sanitize($errors[$name . '_mime']) . '</span>';
        }
        
        if (isset($errors[$name . '_name'])) {
            echo '<span class="text-danger">' . SanitizeHelper::sanitize($errors[$name . '_name']) . '</span>';
        }

        echo '</div></div>';
    }

    public function renderSubmitButton($submit)
    {
        $attributes = '';
        foreach ($submit['attributes'] as $key => $attrValue) {
            $key = SanitizeHelper::sanitize($key);
            $attrValue = SanitizeHelper::sanitize($attrValue);
            $attributes .= ' ' . $key . '="' . $attrValue . '"';
        }
        echo '<div class="d-inline-flex gap-2 mt-3 justify-content-lg-around w-100">';
        echo '<button type="submit" name="SPIRAL_ACTION" value="Next"' . $attributes . ' class="btn px-5 btn-primary">' . SanitizeHelper::sanitize($submit['label']) . '</button>';
        echo '</div>';
    }

    public function renderConfirmationButtons($confirmBack, $confirmSubmit)
    {
        $attributes = '';
        foreach ($confirmBack['attributes'] as $key => $attrValue) {
            $key = SanitizeHelper::sanitize($key);
            $attrValue = SanitizeHelper::sanitize($attrValue);
            $attributes .= ' ' . $key . '="' . $attrValue . '"';
        }
        echo '<div class="d-inline-flex gap-2 mt-3 justify-content-lg-around w-100">';
        echo '<button type="submit" name="SPIRAL_ACTION" value="Back"' . $attributes . ' class="btn px-5 btn-secondary">' . SanitizeHelper::sanitize($confirmBack['label']) . '</button>';

        $attributes = '';
        foreach ($confirmSubmit['attributes'] as $key => $attrValue) {
            $key = SanitizeHelper::sanitize($key);
            $attrValue = SanitizeHelper::sanitize($attrValue);
            $attributes .= ' ' . $key . '="' . $attrValue . '"';
        }
        echo '<button type="submit" name="SPIRAL_ACTION" value="Confirm"' . $attributes . ' class="btn px-5 btn-primary">' . SanitizeHelper::sanitize($confirmSubmit['label']) . '</button>';
        echo '</div>';
    }

    public function renderConfirmationField($field, $value)
    {
        $label = SanitizeHelper::sanitize($field['label']);
        $name = SanitizeHelper::sanitize($field['name']);
        $type = SanitizeHelper::sanitize($field['type']);
        $class = 'confirm-plaintext row mb-3';

        echo '<div class="' . $class . '">';
        if ($label) {
            echo '<label class="col-sm-2 col-form-label"><strong>' . $label . ':</strong></label>';
        }

        echo '<div class="col-sm-10">';
        if ($type === 'image' && $value && $value['base64']) {
            echo '<p><img src="data:' . $value['mime'] . ';base64,' . $value['base64'] . '" alt="Uploaded Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;" /></p>';
        }else if ($type === 'file' && $value && $value['base64']) {
              echo '<p><a href="data:' . $value['mime'] . ';base64,' . $value['base64'] . '" download>' . SanitizeHelper::sanitize($value['name']) . '</a></p>';
        } else if (is_array($value)) {
            echo '<p>' . SanitizeHelper::sanitize(implode(', ', $value)) . '</p>';
        } else {
            if ($type === 'password') {
                echo '<p>***************** </p>';
            } else {
                echo '<p>' . SanitizeHelper::sanitize($value) . '</p>';
            }
        }
        echo '</div></div>';
    }
    
    public function alertMessage($message = ''){
      echo '<div class="alert alert-danger" role="alert">'.$message. '</div>';
    }
}
?>
