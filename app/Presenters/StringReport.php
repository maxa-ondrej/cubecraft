<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\BS4Form;
use Nette\Application\UI\Form as UIForm;
use Nette\Forms\Form;

final class StringReportPresenter extends BasePresenter
{
    protected function createComponentReportForm(): UIForm
    {
        $form = new BS4Form;
        $form->addText('username')
            ->setRequired('Discord Username is required')
            ->addRule(Form::PATTERN, 'Please enter valid Discord Username, e.g. Majksa#1499', '.+#\d{4}');

        $form->addText('row')
            ->setRequired('Row is required')
            ->addRule(Form::INTEGER, 'Please enter valid Row, e.g. 36')
            ->addRule(Form::MIN, 'Please enter valid Row, e.g. 36', 1);

        $form->addText('string_key')
            ->setRequired('String Key is required');

        $form->addText('id_group')
            ->setRequired('Group Id is required')
            ->addRule(Form::INTEGER, 'Please enter valid Group Id, e.g. 25')
            ->addRule(Form::MIN, 'Please enter valid Group Id, e.g. 25', 1);

        $form->addSelect('sheet')
            ->setRequired('Sheet Name is required')
            ->setItems($this->database->fetchPairs("SELECT id_sheet, `value` FROM sheet ORDER BY `value`"));

        $form->addSelect('language')
            ->setRequired('Language is required')
            ->setItems([0 => "All"] + $this->database->fetchPairs("SELECT id_language, `value` FROM `language` ORDER BY `value`"));

        $form->addText('string')
            ->setRequired('Default String is required')
            ->setOption('description', 'Toto číslo zůstane skryté');

        $form->addText('string_new')
            ->setRequired('Corrected String is required');

        $form->addTextArea('comment', 'Comment')
            ->setHtmlAttribute('maxlength', '255')
            ->setHtmlAttribute('placeholder', 'e.g. There is a \' at the end of the string.');
        $form->addSubmit('report', 'Report');
        $form->onSuccess[] = [$this, 'reportFormSucceeded'];
        return $form;
    }

    public function reportFormSucceeded(Form $form, \stdClass $values): void
    {
        $idString = $this->getIdString($values->string_key, $values->sheet, $values->row, $values->id_group, $values->string);
        $this->insertStringLanguage($idString, $values->language);
        $this->database->query('INSERT INTO report', [
            'id_string' => $idString,
            'new_string' => $values->string_new,
            'comment' => $values->comment,
            'user' => $values->username,
        ]);
        $this->redirect('StringReport:reported');
    }

    protected function getIdString(string $stringKey, int $idSheet, $row, $idGroup, string $string): int
    {
        $idString = $this->database->query('SELECT id_string FROM string WHERE', [
            'row' => $row,
        ])->fetchField();
        if (!$idString) {
            $this->database->query('INSERT INTO string', [
                'string_key' => $stringKey,
                'id_sheet' => $idSheet,
                'row' => $row,
                'id_group' => $idGroup,
                'value' => $string
            ]);
            return $this->getIdString($stringKey, $idSheet, $row, $idGroup, $string);
        }
        return $idString;
    }

    protected function insertStringLanguage(int $idString, int $idLanguage): void
    {
        if ($idLanguage != 0) {
            if (!$this->database->fetch('SELECT * FROM string_language WHERE', [
                'id_string' => $idString,
                'id_language' => $idLanguage,
            ])) {
                $this->database->query('INSERT INTO string_language', [
                    'id_string' => $idString,
                    'id_language' => $idLanguage,
                ]);
            }
        } else {
            foreach ($this->database->fetchAll('SELECT id_language FROM `language`') as $language) {
                $this->insertStringLanguage($idString, $language->id_language);
            }
        }
    }

    public function renderResults(): void
    {
        $strings = $this->database->query('SELECT * FROM string')->fetchAll();
        $sheets = [];
        foreach ($strings as $string) {
            $sheet = $this->database->fetchField(
                'SELECT `value` FROM sheet WHERE',
                [
                    'id_sheet' => $string->id_sheet
                ]
            );
            $sheets[$sheet][] = [
                'id' => $string->id_string,
                'row' => $string->row,
                'id_group' => $string->id_group,
                'string_key' => $string->string_key
            ];
        }
        $this->template->sheets = $sheets;
    }

    public function renderResult($idString): void
    {
        $reports = $this->database->fetchAll(
            'SELECT * FROM report WHERE',
            [
                'id_string' => $idString
            ]
        );

        $string = $this->database->fetch(
            'SELECT * FROM string WHERE',
            [
                'id_string' => $idString
            ]
        );

        $languages = [];
        foreach ($this->database->fetchAll(
            'SELECT id_language FROM string_language WHERE',
            [
                'id_string' => $idString
            ]
        ) as $stringLanguage) {
            $languages[] = $this->database->fetchField(
                'SELECT `value` FROM `language` WHERE',
                [
                    'id_language' => $stringLanguage->id_language
                ]
            );
        }
        sort($languages);

        $fixes = [];
        foreach ($reports as $report) {
            $fixes[] = [
                'name' => $report->user,
                'string' => $report->new_string,
                'comment' => $report->comment
            ];
        }

        $this->template->string_key = $string->string_key;
        $this->template->row = $string->row;
        $this->template->id_group = $string->id_group;
        $this->template->string = $string->value;
        $this->template->fixes = $fixes;
        $this->template->languages = join(', ', $languages);
    }
}
