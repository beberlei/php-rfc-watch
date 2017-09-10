<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\CouchDBBundle\DoctrineCouchDBBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new QafooLabs\Bundle\NoFrameworkBundle\QafooLabsNoFrameworkBundle(),
            new AppBundle\AppBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    protected function getEnvParameters()
    {
        $parameters = parent::getEnvParameters();
        $parameters['secret'] = sha1(microtime(true));

        if ($this->getEnvironment() === 'prod') {
            $parameters['assets_base_url'] = null;
        } else {
            $parameters['assets_base_url'] = 'http://localhost:8090';
        }
        $parameters['locale'] = 'en';

        if (isset($_SERVER['COUCHDB_URL'])) {
            $parameters['couchdb_url'] = $_SERVER['COUCHDB_URL'];
        } else {
            $parameters['couchdb_url'] = 'http://localhost:5984/rfcwatch';
        }

        return $parameters;
    }
}
