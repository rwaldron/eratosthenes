<?php

namespace Codebender\LibraryBundle\Handler;

<<<<<<< HEAD
=======
use Codebender\LibraryBundle\Handler\ApiCommand\AbstractApiCommand;
>>>>>>> origin/v2-api-development
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiCommandHandler
{

    protected $entityManager;
    protected $container;

    function __construct(EntityManager $entityManager, ContainerInterface $containerInterface)
    {
        $this->entityManager = $entityManager;
        $this->container = $containerInterface;
    }

    /**
     * This method attempts to match the given API name with
     * the service name of an API. If the API is found, the service
     * is returned. Otherwise, the InvalidApi service is returned.
     *
     * Security Implications: The prefix codebender_api is used for
     * publicly accessible services only. Do NOT prefix an internal/private
     * service with codebender_api as it will then be accessible to the
     * public.
     *
     * @param $content
<<<<<<< HEAD
     * @return InvalidApiCommand
=======
     * @return AbstractApiCommand
>>>>>>> origin/v2-api-development
     */
    public function getService($content)
    {
        $apiPrefix = 'codebender_api.';
        $apiName = $this->removeNonAlphabetic($content['type']);
        $serviceName = $apiPrefix . $apiName;

<<<<<<< HEAD
        if ($this->container->has($serviceName)) {
            $service = $this->container->get($serviceName);
        } else {
            $service = $this->container->get($apiPrefix . 'invalidApi');
        }

        return $service;
=======
        if (!$this->container->has($serviceName)) {
            return $this->container->get($apiPrefix . 'invalidApi');
        }
        return $this->container->get($serviceName);
>>>>>>> origin/v2-api-development
    }

    /**
     * This method removes all non-alphabetic characters from $string
     * and returns the new string after processing.
     *
     * @param $string
     * @return mixed
     */
    private function removeNonAlphabetic($string)
    {
        return preg_replace("/[^A-Za-z]/", '', $string);
    }
}
