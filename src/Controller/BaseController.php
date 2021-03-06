<?php


namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerInterface;
use App\Util\Paginator;
use Symfony\Component\HttpKernel\KernelInterface;

class BaseController extends AbstractController
{

    /**
     * @var int
     */
    private $statusCode = JsonResponse::HTTP_OK;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /** @var KernelInterface */
    private $appKernel;

    public function __construct(SerializerInterface $serializer, KernelInterface $appKernel)
    {
        $this->serializer = $serializer;
        $this->appKernel = $appKernel;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    protected function responseNotFound(string $message = 'Not Found!'): JsonResponse
    {
        return $this->setStatusCode(JsonResponse::HTTP_NOT_FOUND)->respondWithError($message);
    }

    protected function respondInvalidRequest(string $message = 'Invalid Request!'): JsonResponse
    {
        return $this->setStatusCode(JsonResponse::HTTP_BAD_REQUEST)->respondWithError($message);
    }

    protected function respondInternalError(string $message = 'Internal Error'): JsonResponse
    {
        return $this->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR)->respondWithError($message);
    }

    protected function respondWithFile(string $route): BinaryFileResponse
    {
        return new BinaryFileResponse($this->appKernel->getProjectDir() . '/static/' . $route);
    }
    protected function respondWithResource(object $object): JsonResponse
    {
        $data = $this->serializer->serialize($object, 'json');
        return new JsonResponse($data, $this->getStatusCode(), [], true);
    }

    protected function respondCreated(object $object): JsonResponse
    {
        return $this->setStatusCode(JsonResponse::HTTP_CREATED)->respondWithResource($object);
    }

    protected function respondDeleted(): JsonResponse
    {
        return $this->setStatusCode(JsonResponse::HTTP_NO_CONTENT)->respond();
    }

    protected function respondWithPagination(Paginator $paginator): JsonResponse
    {

        $data = array_merge(['items' => (array) $paginator->getResults()], [
            'paginator' => [
                'total_count' => $paginator->getNumResults(),
                'total_pages' => $paginator->getLastPage(),
                'current_page' => $paginator->getCurrentPage(),
                'limit' => $paginator->getPageSize()
            ]
        ]);

        return $this->respond($data);
    }

    protected function respondWithError(string $message): JsonResponse
    {
        return $this->respond([
            'error' => [
                'message' => $message,
                'status_code' => $this->getStatusCode()
            ]
        ]);
    }

    protected function respondWithMessage(string $message = 'All is well.'): JsonResponse
    {
        return $this->respond([
            'response' => [
                'message' => $message,
                'status_code' => $this->getStatusCode()
            ]
        ]);
    }

    protected function respond(array $data = [], array $headers = []): JsonResponse
    {
        $data = $this->serializer->serialize($data, 'json');
        return new JsonResponse($data, $this->getStatusCode(), $headers, true);
    }
}
