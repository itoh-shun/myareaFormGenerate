<?php

class FieldRenderer
{
    public static function renderScript()
    {
      echo '<script>
              function attachFileInputEvent(name, type) {
                  var fileInput = document.getElementById(name);
                  if (fileInput) {
                      fileInput.addEventListener("change", function() {
                          var file = this.files[0];
                          var reader = new FileReader();
                          reader.onloadend = function() {
                              var base64Data = reader.result.split(",")[1];
                              var mimeType = file.type;
                              var filename = file.name;
                              document.getElementById(name + "_base64").value = base64Data;
                              document.getElementById(name + "_mime").value = mimeType;
                              document.getElementById(name + "_name").value = filename;
  
                              if (type === "image") {
                                  document.getElementById(name + "_container").innerHTML = 
                                      "<div id=\"" + name + "_thumbnail\"><img src=\"data:" + mimeType + ";base64," + base64Data + "\" alt=\"Uploaded Image\" style=\"max-width: 100px; max-height: 100px;\" /><button type=\"button\" id=\"" + name + "_clear\" class=\"btn btn-secondary btn-sm mx-2\">Clear</button></div>";
                              } else {
                                  document.getElementById(name + "_container").innerHTML = 
                                      "<div id=\"" + name + "_thumbnail\"><a href=\"data:" + mimeType + ";base64," + base64Data + "\" download=\"" + filename + "\">" + filename + "</a><button type=\"button\" id=\"" + name + "_clear\" class=\"btn btn-secondary btn-sm mx-2\">Clear</button></div>";
                              }
                              attachClearButtonEvent(name, type);
                          }
                          reader.readAsDataURL(file);
                      });
                  }
              }
  
              function attachClearButtonEvent(name, type) {
                  var clearButton = document.getElementById(name + "_clear");
                  if (clearButton) {
                      clearButton.addEventListener("click", function() {
                          document.getElementById(name + "_base64").value = "";
                          document.getElementById(name + "_mime").value = "";
                          document.getElementById(name + "_name").value = "";
                          document.getElementById(name + "_container").innerHTML = "<input type=\"file\" name=\"" + name + "\" id=\"" + name + "\">";
                          attachFileInputEvent(name, type);
                      });
                  }
              }
  
          </script>';
    }


    public static function renderFormStart($action, $csrfToken, $method, $formClass)
    {
        echo '<form action="' . SanitizeHelper::sanitize($action) . '" method="POST" class="' . $formClass . '">';
        echo '<input type="hidden" name="csrf_token" value="' . SanitizeHelper::sanitize($csrfToken) . '">';
        echo '<input type="hidden" name="_method" value="' . SanitizeHelper::sanitize($method) . '">';
    }

    public static function renderFormEnd()
    {
        echo '</form>';
    }

    public static function renderField($field, $values, $errors, $designType)
    {
        $renderer = self::getRenderer($designType);
        $renderer->renderField($field, $values, $errors);
    }

    public static function renderSubmitButton($submit, $designType)
    {
        $renderer = self::getRenderer($designType);
        $renderer->renderSubmitButton($submit);
    }

    public static function renderConfirmationButtons($confirmBack, $confirmSubmit, $designType)
    {
        $renderer = self::getRenderer($designType);
        $renderer->renderConfirmationButtons($confirmBack, $confirmSubmit);
    }

    public static function renderConfirmationField($field, $value, $designType)
    {
        $renderer = self::getRenderer($designType);
        $renderer->renderConfirmationField($field, $value);
    }

    public static function alertMessage($message = '' , $designType){
      $renderer = self::getRenderer($designType);
      $renderer->alertMessage($message);
    }

    private static function getRenderer($designType)
    {
        switch ($designType) {
            case 'bootstrap5':
                return new Bootstrap5Renderer();
            default:
                return new DefaultRenderer();
        }
    }
}
?>
