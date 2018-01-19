<?php namespace CryptoPolice\Bounty\Components;

use Cms\Classes\ComponentBase;
use CryptoPolice\Bounty\Models\Bounty;

class Bounties extends ComponentBase
{

    public $bounty;

    public function componentDetails()
    {
        return [
            'name' => 'Bounty list',
            'description' => 'Bounty Campaign List'
        ];
    }

    public function onRun()
    {
        $this->bounty = Bounty::where('status', 1)->orderBy('sort_order', 'asc')->get();
    }

}