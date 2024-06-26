<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\ItemInterface;

class AuthorController extends AbstractController
{
    #[Route('/api/auteurs', name: 'app_author', methods:['GET'])]
    public function getAllAuthor(AuthorRepository $authorRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $idCache = "getAllAuthor-".$page. "-".$limit;

        $jsonAuthorList = $cache->get($idCache, function (ItemInterface $item) use ($authorRepository, $page, $limit, $serializer) {
            $item->tag("authorCache"); 
            $authorList = $authorRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($authorList, 'json', ['groups' => 'getBooks']); 
        });

        return new JsonResponse($jsonAuthorList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/auteurs/{id}', name: 'detailAuthor', methods:['GET'])]
    public function getAuthor (Author $author, SerializerInterface $serializer): JsonResponse {
        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonAuthor, Response::HTTP_OK, [], true);
    }

    #[Route('/api/auteurs/{id}', name: 'deleteAuthor', methods:['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message:"Vous n'avez pas les droits suffisants pour supprimer un auteur")]
    public function deleteAuthor(Author $author, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
    {   
        $cachePool->invalidateTags(["authorCache"]);
        $em->remove($author);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT); 
    }

    #[Route('/api/auteurs', name: 'createAuthor', methods:['POST'])]
    #[IsGranted('ROLE_ADMIN', message:"Vous n'avez pas les droits suffisants pour créer un auteur")]
    public function createAuthor(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
    {
        $author = $serializer->deserialize($request->getContent(), Author::class, 'json');

        $errors = $validator->validate($author);

        if ($errors->count() >0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        
        $em ->persist($author);
        $em->flush();

        $jsonAuthor = $serializer->serialize($author, 'json', ['groups' => 'getBooks']);

        $location = $urlGenerator->generate('detailAuthor', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonAuthor, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/auteurs/{id}', name: 'updateAuthor', methods:['PUT'])]
    public function updateAuthor(Request $request, SerializerInterface $serializer, Author $currentAuthor, EntityManagerInterface $em, AuthorRepository $authorRepository): JsonResponse
    {
        $updatedAuthor = $serializer->deserialize($request->getContent(), Author::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentAuthor]);
        
        $em ->persist($updatedAuthor);
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

