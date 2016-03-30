<?php
/*
 Copyright 2016-present Google Inc. All Rights Reserved.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 */


/** Uses proselint to lint text */
final class ProseLinter extends ArcanistExternalLinter {

  private $ignoredChecks;

  public function getInfoName() {
    return 'proselint';
  }

  public function getInfoURI() {
    return '';
  }

  public function getInfoDescription() {
    return pht('Use proselint for processing specified files.');
  }

  public function getLinterName() {
    return 'prose';
  }

  public function getLinterConfigurationName() {
    return 'prose';
  }

  public function getLinterConfigurationOptions() {
    return parent::getLinterConfigurationOptions() + array(
      'ignored-checks' => array(
        'type' => 'optional list<string>',
        'help' => 'A list of options to disable',
      ),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'ignored-checks':
        $this->ignoredChecks = $value;
        return;
    }
    parent::setLinterConfigurationValue($key, $value);
  }

  public function getDefaultBinary() {
    return 'proselint';
  }

  public function getInstallInstructions() {
    return pht('Install proselint with `pip install proselint`');
  }

  public function shouldExpectCommandErrors() {
    return false;
  }

  protected function getMandatoryFlags() {
    return array(
      '--json'
    );
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $ok = ($err == 0);

    if (!$ok) {
      return false;
    }

    $results = json_decode($stdout, TRUE);
    $errors = (array)$results['data']['errors'];
    
    if (empty($errors)) {
      return array();
    }

    $messages = array();
    foreach ($errors as $error) {
      if (in_array($error['check'], $this->ignoredChecks)) {
        continue;
      }

      $message = id(new ArcanistLintMessage())
        ->setPath($path)
        ->setLine($error['line'] + 1)
        ->setChar($error['column'])
        ->setCode($error['check'])
        ->setSeverity(ArcanistLintSeverity::SEVERITY_ADVICE)
        ->setName('proselint violoation')
        ->setDescription($error['message']);
      $messages []= $message;
    }
    
    return $messages;
  }
}
