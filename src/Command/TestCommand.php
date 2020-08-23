<?php


namespace App\Command;


use App\Service\CategoryManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    /**
     * @var CategoryManager $categoryManager
     */
    private $categoryManager;

    private $categoriesInfo;

    public function __construct(CategoryManager $categoryManager, string $name = null)
    {
        $this->categoryManager = $categoryManager;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('app:start');
        $this->setDescription('Считываем файлы json и создаем/обновляем на их основе сущности');

        $this->categoriesInfo = file_get_contents('jsonFiles/categories.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->categoryManager->setCategories($this->categoriesInfo);
        return Command::SUCCESS;
    }
}