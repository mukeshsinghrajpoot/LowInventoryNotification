<?php
namespace Bluethinkinc\LowInventoryNotification\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Bluethinkinc\LowInventoryNotification\Helper\Data;
use Magento\Framework\App\State;

class Bluethink extends Command
{
    /**
     * This is a state
     *
     * @var state $state
     */
     protected $state;
    /**
     * This is a helpervalue
     *
     * @var helpervalue $helpervalue
     */
     private $helpervalue;
    /**
     * This is a construct
     *
     * @param State $state
     * @param Data $helpervalue
     */
    public function __construct(
        State $state,
        Data $helpervalue
    ) {
        $this->state = $state;
        $this->helpervalue = $helpervalue;
        parent::__construct();
    }

    /**
     * This is a configure function
     *
     * @return  void
     */
    protected function configure()
    {
        $this->setName('bluethink:lowinventorynotification');
        $this->setDescription('lowinventorynotification command line');
        parent::configure();
    }

   /**
    * This is a execute
    *
    * @return execute
    * @param InputInterface $input
    * @param OutputInterface $output
    */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $data=$this->helpervalue->synclowinventorynotification();
        $output->writeln($data);
        return 0;
    }
}
