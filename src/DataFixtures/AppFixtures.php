<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //creation des auteurs
        $listAuthor = [];
        for ($i = 0; $i<10; $i++) {
            //creation de l'auteur
            $author = new Author();
            $author->setFirstName("Prénom ". $i);
            $author->setLastName("Nom ".$i);
            $manager->persist($author); 
            // on sauvegarde l'auteur dans un tableau
            $listAuthor[] = $author;
        }

        //creation des livres
        for ($i = 0; $i<20; $i++) {
            $book = new Book ();
            $book->setTitle("Titre ". $i);
            $book->setCoverText("Quatrième de couverture numéro : " . $i);
            // on lie le livre à un auteur pris au hasard dans le tableau des auteurs.
            $book->setAuthor($listAuthor[array_rand($listAuthor)]);
            $manager->persist($book);
        }


        $manager->flush();
    }
}
