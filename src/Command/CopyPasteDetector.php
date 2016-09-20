<?php

namespace Webs\QA\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CopyPasteDetector extends BaseCommand
{
    protected $input;
    protected $output;
    protected $source = array('src','app','tests');
    protected $description = 'Copy/Paste Detector';

    protected function configure()
    {
        $this->setName('qa:copy-paste-detector')
            ->setDescription($this->description)
            ->addArgument(
                'source',
                InputArgument::IS_ARRAY|InputArgument::OPTIONAL,
                'List of directories to search  Default:src,app,tests'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $this->input = $input;
        $this->output = $output;
        $this->output->writeln('<comment>Running ' . $this->description . '...</comment>');

        $cpd = 'vendor/bin/phpcpd';
        if(!file_exists($cpd)){
            $process = new Process('phpcpd --help');
            $process->run();
            if ($process->isSuccessful()) {
                $cpd = 'phpcpd';
            } else {
                throw new ProcessFailedException($process);
            }
        }

        $cmd = $cpd . ' ' . $this->getSource() . ' --ansi --fuzzy';
        $process = new Process($cmd);
        $command = $this;
        $process->run(function($type, $buffer) use($command){
            $command->output->writeln($buffer);
        });
        $end = microtime(true);
        $time = round($end-$start);

        $this->output->writeln('<comment>Command executed `' . $cmd . '` in ' . $time . ' seconds</comment>');
        exit($process->getExitCode());
    }

    protected function getSource()
    {
        if($this->input->getArgument('source')){
            $this->source = $this->input->getArgument('source');
        }

        $dirs = array();
        foreach ($this->source as $dir) {
            if(is_dir($dir)){
                $dirs[] = $dir;
            }
        }

        return implode(' ', $dirs);
    }
}