<?php namespace CryptoPolice\Bounty\Components;

use Cms\Classes\ComponentBase;
use CryptoPolice\Bounty\Models\Bounty;

class Bounties extends ComponentBase
{

    public $bountyList;

    public function componentDetails()
    {
        return [
            'name' => 'Bounty list',
            'description' => 'Bounty Campaign List'
        ];
    }

    public function onRun()
    {
        $this->bountyList = Bounty::where('status', 1)->orderBy('sort_order', 'asc')->get();
    }

}
