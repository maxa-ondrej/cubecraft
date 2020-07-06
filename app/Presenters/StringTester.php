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

    public function renderFailed($filename) {
        $data = include $filename;
        $failed = [];
        foreach($data as $row) {
            if(preg_match('/command_.*_name/', $row['key'])) {
                if($row['string'] != $row['translated']) {
                    $failed['Command Name'][] = $row;
                }
            }
            preg_match_all('/&./', $row['string'], $colorCodesString);
            preg_match_all('/&./', $row['translated'], $colorCodesTranslated);
            if(count($colorCodesString) != count($colorCodesTranslated)) {
                $failed['Colour Codes'][] = $row;
            } else {
                foreach($colorCodesString as $key => $colorCode) {
                    if($colorCode != $colorCodesTranslated[$key]) {
                        $failed['Colour Codes'][] = $row;
                        break;
                    }
                }
            }
        }
        $this->template->failed = $failed;
    }
}
