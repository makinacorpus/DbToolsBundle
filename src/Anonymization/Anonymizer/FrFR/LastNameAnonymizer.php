<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\FrFR;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'lastname',
    pack: 'fr_fr',
    description: <<<TXT
    Anonymize with a random french lastname from a sample of ~500 items.
    TXT
)]
class LastNameAnonymizer extends AbstractEnumAnonymizer
{
    /**
     * {@inheritdoc}
     */
    protected function getSample(): array
    {
        return [
           'Martin', 'Bernard', 'Robert', 'Richard', 'Durand', 'Dubois', 'Moreau', 'Simon', 'Laurent',
           'Michel', 'Garcia', 'Thomas', 'Leroy', 'David', 'Morel', 'Roux', 'Girard', 'Fournier',
           'Lambert', 'Lefebvre', 'Mercier', 'Blanc', 'Dupont', 'Faure', 'Bertrand', 'Morin', 'Garnier',
           'Nicolas', 'Marie', 'Rousseau', 'Bonnet', 'Vincent', 'Henry', 'Masson', 'Robin', 'Martinez',
           'Boyer', 'Muller', 'Chevalier', 'Denis', 'Meyer', 'Blanchard', 'Lemaire', 'Dufour', 'Gauthier',
           'Vidal', 'Perez', 'Perrin', 'Fontaine', 'Joly', 'Jean', 'Gautier', 'Roche', 'Roy',
           'Pereira', 'Mathieu', 'Roussel', 'Duval', 'Guerin', 'Lopez', 'Rodriguez', 'Colin', 'Aubert',
           'Lefevre', 'Marchand', 'Schmitt', 'Picard', 'Caron', 'Sanchez', 'Meunier', 'Gaillard', 'Louis',
           'Nguyen', 'Lucas', 'Dumont', 'dos', 'Brunet', 'Clement', 'Brun', 'Arnaud', 'Giraud', 'Barbier',
           'Rolland', 'Charles', 'Hubert', 'Fernandes', 'Fabre', 'Moulin', 'Leroux', 'Dupuis', 'Guillaume',
           'Roger', 'Paris', 'Guillot', 'Dupuy', 'Fernandez', 'Carpentier', 'Payet', 'Ferreira', 'Olivier',
           'Philippe', 'Deschamps', 'Lacroix', 'Jacquet', 'Rey', 'Klein', 'Renaud', 'Baron', 'Leclerc',
           'Royer', 'Berger', 'Bourgeois', 'Bertin', 'Petit', 'Adam', 'Daniel', 'Lemoine', 'Pierre',
           'Francois', 'Goncalves', 'Benoit', 'Lecomte', 'Vasseur', 'Lebrun', 'Leblanc', 'Leclercq',
           'Besson', 'Charpentier', 'Etienne', 'Jacob', 'Michaud', 'Maillard', 'Dumas', 'Monnier', 'Fleury',
           'Aubry', 'Hamon', 'Renard', 'Chevallier', 'Guyot', 'Marty', 'Gomez', 'Gillet', 'Andre', 'Le Boucher',
           'Bailly', 'Pons', 'Renault', 'Julien', 'Huet', 'Riviere', 'Gonzalez', 'Reynaud',
           'Collet', 'Bouvier', 'Millet', 'Rodrigues', 'Gerard', 'Bouchet', 'Schneider', 'Germain', 'Marchal',
           'Martins', 'Breton', 'Cousin', 'Langlois', 'Perrot', 'Perrier', 'Le Noel', 'Pelletier',
           'Mallet', 'Weber', 'Hoarau', 'Chauvin', 'Le Grondin', 'Antoine', 'Boulanger', 'Gilbert',
           'Humbert', 'Guichard', 'Poulain', 'Collin', 'Tessier', 'Pasquier', 'Jacques', 'Lamy',
           'Alexandre', 'Perret', 'Poirier', 'Pascal', 'Gros', 'Buisson', 'Albert', 'Lopes', 'Ruiz', 'Lejeune',
           'Cordier', 'Hernandez', 'Georges', 'Maillot', 'Delaunay', 'Laporte', 'Pichon', 'Voisin', 'Lemaitre',
           'Launay', 'Lesage', 'Carlier', 'Ollivier', 'Gomes', 'Besnard', 'Camus', 'Coulon', 'Cohen', 'Charrier',
           'Paul', 'Didier', 'Guillet', 'Guillou', 'Remy', 'Joubert', 'Bousquet', 'Verdier', 'Hoareau', 'Briand',
           'Raynaud', 'Delmas', 'Coste', 'Blanchet', 'Marin', 'Lebreton', 'Leduc', 'Sauvage', 'Martel', 'Gaudin',
           'Lebon', 'Rossi', 'Diallo', 'Delattre', 'Maury', 'Ribeiro', 'Bigot', 'Menard', 'Guillon', 'Thibault',
           'Colas', 'Raymond', 'Delorme', 'Pineau', 'Joseph', 'Hardy', 'Berthelot', 'Allard', 'Lagarde',
           'Ferrand', 'Valentin', 'Lenoir', 'Tran', 'Bonneau', 'Clerc', 'Godard', 'Tanguy', 'Brunel', 'Gilles',
           'Imbert', 'Seguin', 'Jourdan', 'Alves', 'Bruneau', 'Bodin', 'Morvan', 'Vaillant', 'Marion', 'Devaux',
           'Maurice', 'Courtois', 'Baudry', 'Chauvet', 'Prevost', 'Couturier', 'Turpin', 'Lefort', 'Lacombe',
           'Favre', 'Maire', 'Barre', 'Riou', 'Allain', 'Lombard', 'Mary', 'Lacoste', 'Blin', 'Costa', 'Evrard',
           'Thierry', 'Leveque', 'Loiseau', 'Navarro', 'Laroche', 'Bourdon', 'Texier', 'Carre', 'Levy',
           'Toussaint', 'Grenier', 'Guilbert', 'Guibert', 'Chartier', 'Bonnin', 'Maillet', 'Benard', 'Jacquot',
           'Auger', 'Vallet', 'Leconte', 'Bazin', 'Rousset', 'Fischer', 'Rocher', 'Normand', 'Descamps', 'Potier',
           'Valette', 'Peltier', 'Duhamel', 'Wagner', 'Merle', 'Faivre', 'Barbe', 'Blondel', 'Pottier', 'Pinto',
           'Maurin', 'Guyon', 'Vial', 'Martineau', 'Blot', 'Gallet', 'Foucher', 'Delage', 'Guy', 'Chauveau',
           'Barthelemy', 'Fouquet', 'Boutin', 'Bouvet', 'Salmon', 'Rossignol', 'Neveu', 'Lemonnier', 'Marechal',
           'Herve', 'Delahaye', 'Poncet', 'Bernier', 'Lafon', 'Teixeira', 'Chapuis', 'Pujol', 'Lecoq',
           'Charbonnier', 'Laborde', 'Cros', 'Serre', 'Andrieu', 'Girault', 'Pruvost', 'Berthier', 'Grand',
           'Sabatier', 'Boulay', 'Le Duclos', 'Martinet', 'Hebert', 'Maurel', 'Gervais', 'Dias', 'de Parent',
           'Jourdain', 'Ali', 'Regnier', 'Marc', 'Diaz', 'Billard', 'Favier', 'Bellanger', 'Delannoy', 'Torres',
           'Dubreuil', 'Becker', 'Doucet', 'Gras', 'Prigent', 'Rigaud', 'Samson', 'Masse', 'Cornu', 'Chambon',
           'Mas', 'Fortin', 'Besse', 'Castel', 'Letellier', 'Ricard', 'Benoist', 'Poisson', 'Parmentier', 'Lepage',
           'Boulet', 'Grandjean', 'Claude', 'Mendes', 'Bonhomme', 'Roques', 'Huguet', 'Comte', 'Pommier',
           'Forestier', 'Drouet', 'Constant', 'Leblond', 'Jolly', 'Brault', 'Gosselin', 'Lacour', 'Rose', 'Prat',
           'Geoffroy', 'Hamel', 'Tournier', 'Rault', 'Mounier', 'Ledoux', 'Marquet', 'Blondeau', 'Grange', 'Morand',
           'Picot', 'Millot', 'Brossard', 'Laval', 'Merlin', 'Bocquet', 'Granger', 'Jung', 'Leleu', 'Levasseur',
           'Guillemin', 'Armand', 'Barret', 'Mouton', 'Champion', 'Moreno', 'Bouquet', 'Keller', 'Bourdin',
           'Cartier', 'Gimenez', 'Jamet', 'Lavigne', 'Combes', 'Said', 'Lelievre', 'Guillard', 'Berthet',
           'Guillemot', 'Gibert', 'Leray', 'Gicquel', 'Ferry', 'Fort', 'Dumoulin', 'Provost', 'Basset', 'Papin',
           'Terrier', 'Walter', 'Andrieux', 'Tellier', 'Jeanne', 'Bataille', 'Munoz', 'Jullien', 'Ramos', 'Prieur',
           'Bouchard', 'Saunier', 'Bon', 'Chatelain', 'Foulon', 'Lasserre', 'Granier', 'Cochet', 'Mignot', 'Lang',
           'Prost', 'Vernet', 'Kieffer', 'Madi', 'Gil', 'Jolivet', 'Vallee', 'Schmidt', 'Traore', 'Dijoux', 'Le Weiss',
           'Esnault', 'Vigneron', 'Vacher', 'Tissot', 'Dujardin', 'Pain', 'Soulier', 'Cadet', 'Couderc',
           'Gabriel', 'Lavergne', 'Bois', 'Lefranc', 'Monier', 'Poulet', 'Le Peron', 'Oliveira', 'Jouve', 'Husson',
           'Jouan', 'Gregoire', 'Barreau', 'Lemarchand', 'Arnould', 'Blaise', 'Mahe', 'Bourguignon', 'Cornet', 'Flament',
           'Grosjean', 'Binet', 'Laine', 'Borel', 'Dupin', 'Pasquet', 'Abdou', 'Tardy', 'Lelong', 'Schwartz', 'Proust',
           'Villard', 'Rouxel', 'Lallemand', 'Combe', 'Carvalho', 'Corre', 'Monteiro', 'Roth', 'Lecocq', 'Baudin',
           'Mangin', 'Ragot', 'Bruno', 'Bayle', 'Bonin', 'Magnier', 'Beaumont', 'Rigal', 'Ducrocq', 'André', 'Aubin',
           'Thiery', 'Grimaud', 'Labat', 'Bonnefoy', 'Roland', 'Bureau', 'Bauer', 'Brochard', 'Tavernier', 'Zimmermann',
           'Chollet', 'Moreira', 'Wolff', 'Deshayes', 'Baudouin', 'Godefroy', 'Bastien', 'Montagne', 'Arnoux', 'Villain',
           'Goujon', 'Galland',
        ];
    }
}
