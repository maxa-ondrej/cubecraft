<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\BS4Form;
use Nette\Application\UI\Form as UIForm;
use Nette\Forms\Form;

final class StringTesterPresenter extends BasePresenter
{
    protected function createComponentTsvForm(): UIForm
    {
        $form = new BS4Form;
        $form->addUpload('tsv')
            ->setRequired('Tsv is required');

        $form->addSubmit('check');
        $form->onSuccess[] = [$this, 'tsvFormNext'];
        return $form;
    }

    public function tsvFormNext(Form $form, \stdClass $values): void
    {
        $tsv = file_get_contents($values->tsv->getTemporaryFile());
        $rawRows = preg_split('/\r\n/', $tsv);
        $data = [];
        foreach ($rawRows as $key => $rawRow) {
            $rawColumns = preg_split('/\t/', $rawRow);
            if($key != 0) {
                $data[] = [
                    'row' => $key+1,
                    'key' => $rawColumns[2],
                    'string' => $rawColumns[3],
                    'translated' => $rawColumns[4],
                ];
            }
        }
        while (true) {
            $filename = __DIR__.'/../../temp/' . uniqid('Sheet', true) . '.php';
            if (!file_exists($filename)) {
                break;
            }
        }
        file_put_contents($filename, '<?php return '.var_export($data, true).';');
        $this->redirect('StringTester:failed', $filename);
    }

    public function renderFailed($filename)
    {
        $data = include $filename;
        $failed = [];
        foreach($data as $row) {
            $spacedRow['string'] = str_replace(' ', '·', $row['string']);
            $spacedRow['translated'] = str_replace(' ', '·', $row['translated']);
            if(!$this->checkCommandName($row)) {
                $failed['Command Name'][] = $row;
            }
            if(!$this->checkColorCodes($row)) {
                $failed['Colour Codes'][] = $row;
            }
            if(!$this->checkNumbers($row)) {
                $failed['Numbers'][] = $row;
            }
            if(!$this->checkNumberOfCodes($row)) {
                $failed['Number Of Codes'][] = $row;
            }
            if(!$this->checkVariables($row)) {
                $failed['Variables'][] = $row;
            }
            if(!$this->checkSurroundingSpaces($row)) {
                $failed['Surrounding Spaces'][] = array_replace($row, $spacedRow);
            }
            if(!$this->checkDoubleSpaces($row)) {
                $failed['Double Spaces'][] = array_replace($row, $spacedRow);
            }
        }
        $this->template->failed = $failed;
    }

    protected function checkCommandName(array $row): bool
    {
        if(preg_match('/command_.*_name/', $row['key'])) {
            if($row['string'] != $row['translated']) {
                return false;
            }
        }
        return true;
    }

    protected function checkColorCodes(array $row): bool
    {
        preg_match_all('/&./', $row['string'], $colorCodesString);
        preg_match_all('/&./', $row['translated'], $colorCodesTranslated);
        if(count($colorCodesString[0]) != count($colorCodesTranslated[0])) {
            return false;
        }
        foreach($colorCodesString[0] as $key => $colorCode) {
            if($colorCode != $colorCodesTranslated[0][$key]) {
                return false;
            }
        }
        return true;
    }

    protected function checkNumbers(array $row): bool
    {
        preg_match_all('/\d/', $row['string'], $stringNumbers);
        preg_match_all('/\d/', $row['translated'], $translatedNumbers);
        if(count($stringNumbers[0]) != count($translatedNumbers[0])) {
            return false;
        }
        foreach($stringNumbers as $key => $colorCode) {
            if($colorCode != $translatedNumbers[$key]) {
                return false;
            }
        }
        return true;
    }

    protected function checkNumberOfCodes(array $row): bool
    {
        preg_match_all('/{[^}]+}/', $row['string'], $stringCodes);
        preg_match_all('/{[^}]+}/', $row['translated'], $translatedCodes);
        if(count($stringCodes[0]) != count($translatedCodes[0])) {
            return false;
        }
        return true;
    }

    protected function checkVariables(array $row): bool
    {
        preg_match_all('/{[a-zA-Z\-\_]+}/', $row['string'], $stringVariables);
        preg_match_all('/{[a-zA-Z\-\_}]+}/', $row['translated'], $translatedVariables);
        if(count($stringVariables[0]) === 0 || count($translatedVariables[0]) === 0) {
            return true;
        }
        foreach($stringVariables[0] as $key => $variable) {
            if($variable != $translatedVariables[0][$key]) {
                return false;
            }
        }
        return true;
    }

    protected function checkSurroundingSpaces(array $row): bool
    {
        return $row['translated'] == trim($row['translated']);
    }

    protected function checkDoubleSpaces(array $row): bool
    {
        preg_match_all('/  /', $row['translated'], $doubleSpaces);
        return count($doubleSpaces[0]) === 0;
    }
}
