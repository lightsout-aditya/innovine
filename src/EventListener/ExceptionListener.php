<?php

namespace App\EventListener;

use App\Entity\ErrorLog;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MissingMappingDriverImplementation
     * @throws Exception
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
        if($statusCode !== 404) {
            $message = sprintf(
                'My Error says: %s with code: %s',
                $exception->getMessage(),
                $exception->getCode()
            );

            $response = new Response();
            $response->setContent($message);

            $errorLog = new ErrorLog();
            $errorLog->setUrl($request->getUri());
            $errorLog->setIpAddress($request->getClientIp());
            $errorLog->setErrorCode($statusCode);
            $errorLog->setErrorMessage($exception->getMessage());
            $errorDetail = array(
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            );
            $errorLog->setErrorDetail($errorDetail);

            if (!$this->entityManager->isOpen()) {
                $connection = DriverManager::getConnection(
                    $this->entityManager->getConnection()->getParams(),
                    $this->entityManager->getConfiguration()
                );
                $errorLog->setCreatedAt(date_create());
                $errorLog->setUpdatedAt(date_create());
                $this->entityManager = new EntityManager($connection, $this->entityManager->getConfiguration());
            }

            $this->entityManager->persist($errorLog);
            $this->entityManager->flush();
        }
    }
}