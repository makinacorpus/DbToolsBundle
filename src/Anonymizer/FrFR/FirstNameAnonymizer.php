<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymizer\Core\EnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'firstname',
    pack: 'fr_fr',
    description: <<<TXT
    Anonymize with a random french firstname from a sample of ~500 items.
    TXT
)]
class FirstNameAnonymizer extends EnumAnonymizer
{
    /**
     * {@inheritdoc}
     */
    protected function getSample(): array
    {
        return [
            "Jean", "Marie", "Michel", "Nathalie", "Sylvie", "Philippe", "Thierry", "Alain", "Isabelle",
            "Stéphanie", "Stéphane", "Martine", "Christophe", "Valérie", "Nicolas", "Patrick", "Daniel",
            "Pascal", "Sandrine", "Eric", "Gérard", "Laurent", "Catherine", "Bernard", "David", "Sébastien",
            "André", "Frédéric", "Brigitte", "Christian", "Julien", "Monique", "Jeanne", "Pierre",
            "Véronique", "Christine", "Nicole", "Olivier", "Jacques", "Françoise", "René", "Claude",
            "Marcel", "Aurélie", "Didier", "Céline", "Roger", "Kevin", "Chantal", "Christelle", "Jerome",
            "Bruno", "Corinne", "Jacqueline", "Danielle", "Dominique", "Christiane", "Patricia", "Elodie",
            "Laurence", "Karine", "Louis", "Emilie", "Robert", "Annie", "Henri", "Georges", "Sophie",
            "Jeannine", "Franck", "Léa", "Virginie", "Jean-pierre", "Thomas", "Michèle", "Dominique",
            "Jean-claude", "Maurice", "Madeleine", "Julie", "Romain", "Suzanne", "Guy", "Guillaume", "Lucas",
            "Cedric", "Marguerite", "Laura", "Anthony", "Yvonne", "Alexandre", "Maxime", "Théo", "Enzo",
            "Paul", "Joseph", "Marcelle", "Jonathan", "Fabrice", "Pascale", "Denise", "Laetitia", "Germaine",
            "Audrey", "Florence", "Manon", "Marine", "Paulette", "Jeremy", "Yvette", "Sandra", "Odette",
            "Serge", "Evelyne", "Hugo", "Raymond", "Gilles", "Emmanuel", "Quentin", "Delphine", "Camille",
            "Renée", "Simone", "Sabrina", "Severine", "Joël", "Nadine", "Louise", "Chloé", "Vincent", "Marion",
            "Emma", "Andrée", "Nathan", "Hervé", "Florian", "Mathieu", "Dylan", "Fabienne", "Ginette",
            "Josiane", "Antoine", "Ludovic", "Anne", "Josette", "Marc", "Colette", "Damien", "Anaïs", "Annick",
            "Claudine", "Yves", "Jordan", "Jennifer", "Jocelyne", "Lucienne", "Benjamin", "Océane", "Albert",
            "Caroline", "Carole", "Lucien", "Béatrice", "Jean-luc", "Arnaud", "Mathis", "Denis", "Pauline",
            "Clara", "Clément", "Joëlle", "Fabien", "Gabriel", "Vanessa", "Alexis", "François", "Michelle",
            "Mélanie", "Valentin", "Micheline", "Patrice", "Francis", "Georgette", "Raymonde", "Mickael", "Jade",
            "Sarah", "Daniele", "Thérèse", "Justine", "Gilbert", "Charles", "Cécile", "Jessica", "Liliane",
            "Jules", "Mathilde", "Bernadette", "Simonne", "Geneviève", "Gregory", "Mireille", "Emile",
            "Amandine", "Lola", "Inès", "Ethan", "Sylvain", "Marie-christine", "Alexandra", "Adam", "Léo",
            "Benoît", "Raphaël", "Charlotte", "Sonia", "Marthe", "Tom", "Cyril", "Michael", "Elisabeth",
            "Jean-marc", "Adrien", "Henriette", "Nolan", "Gisèle", "Arthur", "Lucie", "Maryse", "Carine",
            "Jean-paul", "Amélie", "Eliane", "Mathéo", "Angelique", "Yanis", "Hélène", "Xavier", "Maria", "Lionel",
            "Zoé", "Huguette", "Noah", "Baptiste", "Magali", "Florent", "Corentin", "Anne-marie", "Claire",
            "Jean-michel", "Ophélie", "Agnès", "Aurélien", "Lilou", "Emmanuelle", "Fernand", "Alice", "Roland",
            "Timéo", "Maelys", "Sabine", "Marie-thérèse", "Cindy", "Liam", "Léon", "Morgane", "Jacky", "Eugène",
            "Lisa", "Jean-louis", "Mattéo", "Yannick", "Loïc", "Maël", "Aurore", "Jean-françois", "Muriel", "Jean-marie",
            "Matthieu", "Lina", "Ambre", "Eva", "Fanny", "Yann", "Arlette", "Josephine", "Axel", "Alba", "Mila",
            "Maeva", "Mélissa", "Adeline", "Romane", "Marie-claude", "Sacha", "Gaston", "Fernande", "Myriam", "Berthe",
            "Ghislaine", "Léna", "Evan", "Eugénie", "Juliette", "Francine", "Mael", "Auguste", "Estelle", "Marie-france",
            "Margaux", "Noa", "Gabin", "Timeo", "Victor", "Louna", "Coralie", "Cyrille", "Odile", "Rose", "Pierrette",
            "Tiago", "Maxence", "Jean-jacques", "Janine", "Mohamed", "Frédérique", "Rémi", "Anna", "Mia", "Isaac",
            "Clémence", "Célia", "Richard", "Noe", "Lucette", "Sylviane", "Christopher", "Aaron", "Nadia", "Eden",
            "Laure", "Romy", "Régine", "Julia", "Gaelle", "Bastien", "Simon", "Peggy", "Noemie", "Samuel", "Viviane",
            "Lou", "Naël", "Thibault", "Maryvonne", "Elisa", "Noé", "Megane", "Geoffrey", "Murielle", "Nadege",
            "Gabrielle", "Katia", "Aline", "Solange", "Charlène", "Irène", "Claudette", "Ines", "Yoann", "Maëlys",
            "Rayan", "Martin", "Luc", "José", "Geraldine", "Christel", "Gilberte", "Angèle", "Bertrand", "Steven",
            "Dorian", "Maryline", "Regis", "Alicia", "Kylian", "Marius", "Robin", "Agathe", "Antoinette", "Carla",
            "Iris", "Margot", "Alfred", "Alison", "Jeanine", "Jean-philippe", "Louane", "Alma", "Marceau", "Nina",
            "Marie-josé", "Malo", "Mathys", "Augustine", "Killian", "Esteban", "Cassandra", "Gaëtan", "Elise", "Lea",
            "Claude", "Ludivine", "Laurine", "Yolande", "Dimitri", "Jimmy", "Inaya", "Tristan", "Chloe", "Nino",
            "Jean-christophe", "Charlie", "Ayden", "Ernest", "Marina", "Marie-hélène", "Rachel", "Victoire", "Leo",
            "Mauricette", "Luna", "Valentine", "Giulia", "Lana", "Léonie", "Natacha", "Emilienne", "Marie-laure", "Milo",
            "Ingrid", "Adèle", "Alexia", "Marie-pierre", "Anne-sophie", "Jean-yves", "Maelle", "Lily", "Bryan", "Blanche",
            "Laurie", "Lena", "Lorenzo", "Raphael", "Lenny", "Gaspard", "Magalie", "Gérald", "Amir", "William", "Olivia",
            "Camille", "Edith", "Rémy", "Tony", "Jeremie", "Sofia", "Pamela", "Nelly", "Lydie", "Gwendoline", "Marie-claire",
            "Lyam", "Augustin", "Mehdi", "Eliott", "Victoria", "Matéo", "Edmond", "Solène", "Alphonse", "Erwan", "Linda",
            "Karim", "Elsa", "Ilona", "Jérôme", "Edouard", "Emeline", "Barbara", "Maud", "Candice", "Morgan", "Mathias",
            "Johanna", "Titouan", "Jean-baptiste", "Flavie", "Thibaut", "Elio", "Theo", "Rolande", "Paule", "Hubert",
            "Lilian", "Armand", "Marjorie", "Manuel", "Marie-françoise", "Alix", "Christèle", "Zoe", "Nour", "Noémie",
            "Andréa", "Johan", "Allan", "Mya", "Ibrahim", "Yvon", "Aude", "Coline", "Jason", "Antonin", "Samantha",
            "Marielle", "Brandon", "Léane", "Cédric", "Cathy", "Mélina", "Clement", "Deborah", "Léana", "Amine", "Côme",
            "Marie-line", "Lya", "Axelle", "Yasmine", "Marie-louise", "Noël", "Kelly", "Matheo", "Cynthia", "Imran",
            "Sohan", "Kenza", "Benedicte", "Mylène", "Lyana", "Elena", "Adrienne", "Capucine", "Corine", "Tanguy", "Lucile",
            "Annette", "Eloise", "Aya", "Enora", "Brice", "Jordy", "Ryan", "Alizée", "Séverine", "Kévin", "Ilyes", "Aime",
            "Etienne", "Gustave", "Kaïs", "Anaelle", "Théa", "Léontine", "Emy", "Yohann", "Ava", "Alex", "France", "Jackie",
            "Sara", "Salomé", "Alessio", "Wendy", "Léandre", "Noam", "Naïm", "Soan", "Anne-laure", "Lydia", "Noëlle",
            "Steve", "Sylvia", "Albertine", "Ilan", "Ismaël", "Renaud", "Jérémy",
        ];
    }
}
