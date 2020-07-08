<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\BS4Form;
use App\Model\Checker;
use App\Model\Row;
use App\Model\Tools;
use Nette\Application\UI\Form as UIForm;
use Nette\Forms\Form;

final class StringTesterPresenter extends BasePresenter
{
    /**
     * Creates the form
     *
     * @return UIForm
     */
    protected function createComponentTsvForm(): UIForm
    {
        $form = new BS4Form;
        $form->addUpload('tsv')
            ->setRequired('Tsv is required');

        $form->addSubmit('check');
        $form->onSuccess[] = [$this, 'tsvFormNext'];
        return $form;
    }

    /**
     * Renders all nonstranslated words
     *
     * @return void
     */
    public function renderWords()
    {
        $this->template->words = Checker::NON_TRANSLATED_WORDS;
    }

    /**
     * Handles the form
     *
     * @param Form $form
     * @param \stdClass $values
     * @return void
     */
    public function tsvFormNext(Form $form, \stdClass $values): void
    {
        $rows = $this->generateRowsFromFile($values->tsv->getTemporaryFile());
        $failed = Checker::check($rows);
        if (count($failed) == 0) {
            $this->redirect('StringTester:success', $values->tsv->getName());
        }
        $tempFilename = Tools::generateTempFilename();
        file_put_contents($tempFilename, serialize($failed));
        $this->redirect('StringTester:failed', [
            $values->tsv->getName(),
            $tempFilename
        ]);
    }


    /**
     * Generates rows from file
     *
     * @param string $filename
     * @return array
     */
    protected function generateRowsFromFile(string $filename): array
    {
        $tsv = file_get_contents($filename);
        $rawRows = preg_split('/\r\n/', $tsv);
        $rows = [];
        foreach ($rawRows as $key => $rawRow) {
            $rawColumns = preg_split('/\t/', $rawRow);
            if($rawColumns[2] == '') {
                break;
            }
            if ($key != 0) {
                $row = new Row;
                $row->row = $key + 1;
                $row->key = $rawColumns[2];
                $row->default = $rawColumns[3];
                $row->translated = $rawColumns[4];
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function renderFailed($filename, $temp)
    {
        $failed = unserialize(file_get_contents($temp));
        $this->template->failed = $failed;
        $this->template->filename = $filename;
    }

    public function renderSuccess($filename)
    {
        $this->template->filename = $filename;
    }
}
