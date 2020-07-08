<?php

namespace App\Model;


class Checker
{
    public const NON_TRANSLATED_WORDS = [
        'Games' => [
            'Lucky Islands', 'EggWars', 'SkyWars', 'MinerWare', 'Tower Defence', 'SkyBlock', 'BlockWars',
            'Quake Craft', 'QuakeCraft', 'Battle Zone', 'BattleZone', 'Paintball', 'Layer Spleef',
            'Wing Rush', 'Archer Assault', 'Line Dash', 'Survival Games', 'Slime Survival',
        ],
        'Ranks' => [
            'Stone', 'Iron', 'Gold', 'Lapiz', 'Diamond', 'Emerald', 'Obsidian', 'Plus', 'Helper', 'Moderator', 'Developer', 'Designer', 'Translator',
        ],
        'CubeCraft Custom Words' => [
            'Cubelet', 'CubeCraft', 'CubeCraft Games',
        ],
        'Abbreviations' => [
            'FFA', 'MVP', 'VIP', 'PvP', 'CTF',
        ],
        'Maps' => [
            'Spring','Carrots', 'Hatch', 'Chocolate', 'Easter', 'Bunny', 'Hunt', 'Rabbit', 'Eggs',
        ],
    ];

    public const
        COMMAND_NAME_REG = '/command_.*_name/',
        COLOR_REG = '/&./',
        NUMBER_REG = '/(?!&)\d/',
        CODE_REG = '/{[^}]+}/',
        VARIABLE_REG = '/{[a-zA-Z\-\_]+}/',
        DOUBLE_SPACE_REG = '/ {2}/',
        DOUBLE_DOT_REG = '/^[^\.]*\.{2}[^\.]*$/';

    /**
     * Check
     *
     * @param array $rows
     * @return array
     */
    public static function check(array $rows): array
    {
        $failed = [];
        foreach ($rows as $row) {
            if (!is_null($newRow = self::checkCommandName(clone $row))) {
                $failed['Command Name'][] = $newRow;
            }
            if (!is_null($newRow = self::checkColorCodes(clone $row))) {
                $failed['Colour Codes'][] = $newRow;
            }
            if (!is_null($newRow = self::checkNumbers(clone $row))) {
                $failed['Numbers'][] = $newRow;
            }
            if (!is_null($newRow = self::checkNumberOfCodes(clone $row))) {
                $failed['Number Of Codes'][] = $newRow;
            }
            if (!is_null($newRow = self::checkVariables(clone $row))) {
                $failed['Variables'][] = $newRow;
            }
            if (!is_null($newRow = self::checkSurroundingSpaces(clone $row))) {
                $failed['Surrounding Spaces'][] = $newRow;
            }
            if (!is_null($newRow = self::checkDoubleSpaces(clone $row))) {
                $failed['Double Spaces'][] = $newRow;
            }
            if (!is_null($newRow = self::checkTrailingDots(clone $row))) {
                $failed['Trailing Dots'][] = $newRow;
            }
            if (!is_null($newRow = self::checkDoubleDots(clone $row))) {
                $failed['Double Dots'][] = $newRow;
            }
            if (!is_null($newRow = self::checkNontranslatedWords(clone $row))) {
                $failed['Should Not Be Translated'][] = $newRow;
            }
        }
        return $failed;
    }

    protected static function checkCommandName(Row $row): ?Row
    {
        if (preg_match(self::COMMAND_NAME_REG, $row->key)) {
            if ($row->default != $row->translated) {
                $row->default = Tools::colorize(Tools::DANGER, $row->default);
                $row->translated = Tools::colorize(Tools::DANGER, $row->translated);
                return $row;
            }
        }
        return null;
    }

    protected static function checkColorCodes(Row $row): ?Row
    {
        preg_match_all(self::COLOR_REG, $row->default, $colorCodesString);
        preg_match_all(self::COLOR_REG, $row->translated, $colorCodesTranslated);
        if (count($colorCodesString[0]) != count($colorCodesTranslated[0])) {
            //TODO
            return $row;
        }
        foreach ($colorCodesString[0] as $key => $colorCode) {
            if ($colorCode != $colorCodesTranslated[0][$key]) {
                $row->default = Tools::str_replace_nth(self::COLOR_REG, Tools::colorize(Tools::DANGER, $colorCodesString[0][$key]), $row->default, $key);
                $row->translated = Tools::str_replace_nth(self::COLOR_REG, Tools::colorize(Tools::DANGER, $colorCodesTranslated[0][$key]), $row->translated, $key);
                return $row;
            }
        }
        return null;
    }

    protected static function checkNumbers(Row $row): ?Row
    {
        preg_match_all(self::NUMBER_REG, $row->default, $stringNumbers);
        preg_match_all(self::NUMBER_REG, $row->translated, $translatedNumbers);
        if (count($stringNumbers[0]) != count($translatedNumbers[0])) {
            //TODO
            return $row;
        }
        foreach ($stringNumbers[0] as $key => $number) {
            if ($number != $translatedNumbers[0][$key]) {
                $row->default = Tools::str_replace_nth(self::NUMBER_REG, Tools::colorize(Tools::DANGER, $stringNumbers[0][$key]), $row->default, $key);
                $row->translated = Tools::str_replace_nth(self::NUMBER_REG, Tools::colorize(Tools::DANGER, $translatedNumbers[0][$key]), $row->translated, $key);
                return $row;
            }
        }
        return null;
    }

    protected static function checkNumberOfCodes(Row $row): ?Row
    {
        preg_match_all(self::CODE_REG, $row->default, $stringCodes);
        preg_match_all(self::CODE_REG, $row->translated, $translatedCodes);
        if (count($stringCodes[0]) != count($translatedCodes[0])) {
            //TODO
            return $row;
        }
        return null;
    }

    protected static function checkVariables(Row $row): ?Row
    {
        preg_match_all(self::VARIABLE_REG, $row->default, $stringVariables);
        preg_match_all(self::VARIABLE_REG, $row->translated, $translatedVariables);
        if (count($stringVariables[0]) === 0 && count($translatedVariables[0]) === 0) {
            return null;
        } elseif (count($stringVariables[0]) === 0 || count($translatedVariables[0]) === 0) {
            //TODO
            return $row;
        }
        foreach ($stringVariables[0] as $key => $variable) {
            if ($variable != $translatedVariables[0][$key]) {
                $row->default = Tools::str_replace_nth(self::VARIABLE_REG, Tools::colorize(Tools::DANGER, $stringVariables[0][$key]), $row->default, $key);
                $row->translated = Tools::str_replace_nth(self::VARIABLE_REG, Tools::colorize(Tools::DANGER, $translatedVariables[0][$key]), $row->translated, $key);
                return $row;
            }
        }
        return null;
    }

    protected static function checkSurroundingSpaces(Row $row): ?Row
    {
        if ($row->translated !== trim($row->translated)) {
            $row->default = str_replace(' ', '·', $row->default);
            $row->translated = str_replace(' ', '·', $row->translated);
            return $row;
        }
        return null;
    }

    protected static function checkDoubleSpaces(Row $row): ?Row
    {
        preg_match_all(self::DOUBLE_SPACE_REG, $row->translated, $doubleSpaces);
        if (count($doubleSpaces[0]) !== 0) {
            $row->default = str_replace(' ', '·', $row->default);
            $row->translated = str_replace(' ', '·', $row->translated);
            return $row;
        }
        return null;
    }

    protected static function checkTrailingDots(Row $row): ?Row
    {
        $lastStringChar = substr($row->default, -1);
        $lastTranslatedChar = substr($row->translated, -1);
        if ($lastStringChar === '.' || $lastTranslatedChar === '.') {
            if ($lastStringChar === '.') {
                $row->default = substr($row->default, 0, -1). Tools::colorize(Tools::DANGER, $lastStringChar);
            }
            if ($lastTranslatedChar === '.') {
                $row->translated = substr($row->translated, 0, -1). Tools::colorize(Tools::DANGER, $lastTranslatedChar);
            }
            if ($lastStringChar !== $lastTranslatedChar) {
                return $row;
            }
        }
        return null;
    }

    protected static function checkDoubleDots(Row $row): ?Row
    {
        preg_match_all(self::DOUBLE_DOT_REG, $row->translated, $doubleDots);
        if (count($doubleDots[0]) !== 0) {
            preg_replace(self::DOUBLE_DOT_REG, Tools::colorize(Tools::DANGER, '..'), $row->translated);
            return $row;
        }
        return null;
    }

    protected static function checkNontranslatedWords(Row $row): ?Row
    {
        foreach (self::NON_TRANSLATED_WORDS as $category=>$words) {
            if($category === 'Maps') {
                if(!preg_match('/map/',$row->key.$row->default)) {
                    break;
                }
            }
            if($category === 'Ranks') {
                if(!preg_match('/rank/',$row->key.$row->default)) {
                    break;
                }
            }
            foreach ($words as $word) {
                preg_match_all('/' . $word . '/', $row->default, $stringWords);
                preg_match_all('/' . $word . '/', $row->translated, $translatedWords);
                if (count($stringWords[0]) > count($translatedWords[0])) {
                    return $row;
                }
            }
        }
        return null;
    }
}
