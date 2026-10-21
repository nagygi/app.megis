<?php

use App\Services\Import\SpreadsheetReader;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;

it('gives the computed value of a formula cell, not the formula (NM-19)', function () {
    $row = new Row([
        new StringCell('M-001', null),
        new FormulaCell('=IF($AM2>=2,"használt","egy esetben láttuk")', 'egy esetben láttuk'),
        new FormulaCell('=SUMIFS(Találat!T:T,Találat!C:C,$A2)', 2),
    ]);

    expect((new SpreadsheetReader)->cellValues($row))->toBe(['M-001', 'egy esetben láttuk', '2']);
});

it('keeps the place of a missing cell, so the columns stay in line', function () {
    $row = new Row([0 => new StringCell(' RC-01 ', null), 1 => new EmptyCell(null, null), 3 => new NumericCell(3, null)]);

    expect((new SpreadsheetReader)->cellValues($row))->toBe(['RC-01', '', '', '3']);
});
