<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'lastname',
    pack: 'core',
    description: <<<TXT
    Anonymize with a random lastname from a sample of ~1000 items.
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
            'Wang', 'Li', 'Zhang', 'Chen', 'Liu', 'Devi', 'Yang', 'Huang', 'Singh', 'Wu',
            'Kumar', 'Xu', 'Ali', 'Zhao', 'Zhou', 'Nguyen', 'Khan', 'Ma', 'Lu', 'Zhu', 'Maung',
            'Sun', 'Yu', 'Lin', 'Kim', 'He', 'Hu', 'Jiang', 'Guo', 'Ahmed', 'Khatun', 'Luo',
            'Akter', 'Gao', 'Zheng', 'da Silva', 'Tang', 'Liang', 'Das', 'Wei', 'Mohamed',
            'Islam', 'Shi', 'Song', 'Xie', 'Han', 'Garcia', 'Mohammad', 'Tan', 'Deng', 'Bai',
            'Ahmad', 'Yan', 'Kaur', 'Feng', 'Hernandez', 'Rodriguez', 'Cao', 'Lopez', 'Hassan',
            'Hussain', 'Gonzalez', 'Martinez', 'Ceng', 'Ibrahim', 'Peng', 'Cai', 'Xiao', 'Tran',
            'Pan', 'dos Santos', 'Cheng', 'Yuan', 'Rahman', 'Yadav', 'Su', 'Perez', 'I', 'Le',
            'Fan', 'Dong', 'Ye', 'Ram', 'Tian', 'Fu', 'Hossain', 'Kumari', 'Sanchez', 'Du',
            'Pereira', 'Yao', 'Zhong', 'Jin', 'Pak', 'Ding', 'Mohammed', 'Lal', 'Yin', 'Bibi',
            'Silva', 'Muhammad', 'Ren', 'Ferreira', 'Liao', 'Mandal', 'Cui', 'Begum', 'Fang',
            'Sharma', 'Alves', 'Shah', 'Ray', 'Qiu', 'Meng', 'Ramirez', 'Mondal', 'Dai', 'Kang',
            'Patel', 'Wen', 'Gu', 'Gomez', 'Pham', 'Jia', 'Sah', 'Xia', 'Hong', 'Abdul', 'Rodrigues',
            'Smith', 'Santos', 'Diaz', 'Hou', 'Hasan', 'Xiong', 'Zou', 'Alam', 'Prasad', 'de Oliveira',
            'Qin', 'Choe', 'Ji', 'Uddin', 'Musa', 'Gong', 'Ghosh', 'Chang', 'Flores', 'Diallo', 'Gomes',
            'Xue', 'Lei', 'Patil', 'Torres', 'de Souza', 'Qi', 'Lai', 'Cruz', 'Long', 'Ramos', 'Hussein',
            'Fernandez', 'Duan', 'Ri', 'An', 'Shaikh', 'Bakhash', 'Xiang', 'Pal', 'Morales', 'Allah', 'Wan',
            'Johnson', 'Reyes', 'Abdullahi', 'Tao', 'Gupta', 'Jimenez', 'Mao', 'Biswas', 'Kong', 'Hoang',
            'Williams', 'Abubakar', 'Abbas', 'Sahu', 'Gutierrez', 'Chong', 'Hao', 'Shao', 'Saha', 'Guan',
            'Mo', 'Ruiz', 'Khatoon', 'Oliveira', 'Qian', 'Roy', 'Saleh', 'Abdullah', 'Lan', 'Sarkar', 'Sani',
            'Castillo', 'Alvarez', 'Brown', 'Martin', 'Jones', 'Mendoza', 'Romero', 'Iqbal', 'Qu', 'Begam',
            'Rana', 'Castro', 'Ansari', 'Yi', 'Usman', 'Traore', 'Bao', 'Sekh', 'Rojas', 'Bi', 'Mahmoud',
            'Martins', 'Ortiz', 'Vu', 'Moreno', 'de Jesus', 'Malik', 'Ribeiro', 'Lee', 'Mahato', 'Ullah',
            'Ismail', 'Fernandes', 'Rani', 'Paramar', 'Thomas', 'John', 'Ge', 'Phan', 'Rivera', 'Chu',
            'Adamu', 'Mahto', 'Tong', 'Vargas', 'Niu', 'Xing', 'Joseph', 'Lopes', 'Cho', 'Osman', 'Nayak',
            'Umar', 'Pang', 'Rathod', 'Jadhav', 'Bui', 'Chand', 'Zhan', 'Mia', 'Coulibaly', 'Barman', 'Soares',
            'Sato', 'You', 'Ni', 'Khaled', 'Chan', 'Di', 'Saeed', 'Mishra', 'Herrera', 'Thakur', 'Barbosa',
            'Zhuang', 'Behera', 'Adam', 'Lima', 'Sultana', 'Suzuki', 'Medina', 'Din', 'Ho', 'Bano', 'Costa',
            'Aguilar', 'O', 'Dias', 'Dang', 'Paswan', 'Muñoz', 'Qiao', 'Muhammed', 'Yusuf', 'Abdi', 'Miller',
            'Chowdhury', 'Vo', 'Camara', 'Ahamad', 'Omar', 'Akhtar', 'Ouedraogo', 'Shen', 'Gul', 'Mai',
            'Vieira', 'Davis', 'Nie', 'Wilson', 'Mendez', 'Batista', 'Majhi', 'Souza', 'Ou', 'Sardar', 'Paul',
            'Ha', 'Vazquez', 'Thakor', 'Miranda', 'Vasquez', 'Haque', 'Haji', 'Chauhan', 'Amin', 'Yue',
            'Huynh', 'Sayed', 'Rashid', 'Pawar', 'Chavez', 'Shang', 'Tu', 'Gan', 'Rai', 'Pradhan', 'Naik',
            'Do', 'Karim', 'James', 'Taylor', 'Geng', 'Ngo', 'Hossen', 'de Sousa', 'Jahan', 'Salazar',
            'Yun', 'da Costa', 'Kone', 'Tanaka', 'Moussa', 'Nawaz', 'Mustafa', 'Mi', 'Mou', 'Guzman',
            'Jiao', 'Rao', 'Juma', 'Zuo', 'Watanabe', 'Anderson', 'Dan', 'Moreira', 'Ilunga', 'Takahashi',
            'Sheikh', 'Shinde', 'Hamid', 'Bello', 'Aliyu', 'Pu', 'Akhter', 'Nath', 'Mendes', 'Ngoy',
            'Suarez', 'Jackson', 'Aziz', 'Ortega', 'Cardoso', 'Ba', 'Molla', 'Garba', 'Campos', 'Pinto',
            'Ashraf', 'Khalil', 'Jean', 'Delgado', 'Noor', 'Truong', 'Nunes', 'Shu', 'Miah', 'Anwar', 'Sin',
            'Almeida', 'Molina', 'Ke', 'Ito', 'Sari', 'Ling', 'Dominguez', 'Banda', 'Chandra', 'Thompson',
            'Contreras', 'Caudhari', 'da Conceiçao', 'Hua', 'Aslam', 'Ei', 'de Lima', 'Araujo', 'Rocha', 'Shaik',
            'Ivanova', 'Raut', 'Ruan', 'Guerrero', 'David', 'Peter', 'Soto', 'Acosta', 'Ivanov', 'Jha', 'Santana',
            'Bala', 'White', 'Duong', 'Ning', 'Tesfaye', 'Moore', 'Sultan', 'Mejia', 'Solomon', 'Ghulam',
            'Zaman', 'Ouattara', 'Weng', 'Mei', 'Issa', 'Yamamoto', 'Lam', 'Navarro', 'Nakamura', 'Machado',
            'Andrade', 'Bauri', 'Said', 'Simon', 'Raj', 'So', 'Barry', 'Ramadan', 'do Nascimento', 'Vega', 'Saad',
            'Alvarado', 'Patra', 'Espinoza', 'Abdel', 'Cabrera', 'Lian', 'Rios', 'Murmu', 'Yılmaz', 'Mehmood',
            'Salem', 'Teixeira', 'Leon', 'Marques', 'Chi', 'Mostafa', 'Solanki', 'Harris', 'Kobayashi', 'Huo',
            'Xin', 'Schmidt', 'Bah', 'Pandey', 'Jing', 'Idris', 'Khaw', 'Müller', 'Sow', 'Duarte', 'Nuñez', 'Manuel',
            'Miao', 'Dutta', 'Sheng', 'Prakash', 'Pei', 'Rosa', 'Kato', 'Aung', 'Cauhan', 'Im', 'Chon', 'Saito', 'Peña',
            'May', 'Gonzales', 'Francisco', 'Awad', 'Correa', 'Sawadogo', 'Perera', 'Ran', 'Haruna', 'Sinh', 'Santiago',
            'Min', 'de Almeida', 'Hwang', 'Pandit', 'Ta', 'Toure', 'Mu', 'Ko', 'Chai', 'Khin', 'Aktar', 'Munda',
            'Robinson', 'Suleiman', 'Chakraborty', 'Sharif', 'Juarez', 'Patal', 'Kamal', 'Jain', 'Phiri', 'Salah',
            'Walker', 'Akbar', 'Clark', 'Lewis', 'Hosen', 'Diarra', 'Avila', 'Chaudhary', 'Chaudhari', 'Franco',
            'Ndiaye', 'Arias', 'Akther', 'Pathan', 'Charles', 'Luna', 'Pacheco', 'Samuel', 'Marquez', 'Saw', 'Mohammadi',
            'Carvalho', 'Salim', 'Qasim', 'Hamza', 'Emmanuel', 'Rehman', 'Bautista', 'Nascimento', 'Hoque', 'Fernando',
            'Mahmud', 'Salman', 'Kabir', 'Kamble', 'Bashir', 'Manjhi', 'Ou yang', 'Sousa', 'Aye', 'Cha', 'Fuentes', 'Domingos',
            'Marin', 'Cisse', 'Adams', 'Keita', 'Dou', 'Hall', 'King', 'Abdalla', 'Habib', 'Young', 'Monteiro', 'Debnath',
            'Isa', 'Daniel', 'Son', 'Sresth', 'Getachew', 'Bian', 'Abdallah', 'Husain', 'Jena', 'Kasongo', 'Wright',
            'Abdou', 'Ai', 'Allen', 'Makavan', 'Kaya', 'Thapa', 'Yoshida', 'Giri', 'Abdo', 'Yahaya', 'Akram', 'Mora',
            'Kazem', 'Saleem', 'Siddique', 'Baba', 'Yamada', 'Teng', 'Imran', 'Jie', 'Sandoval', 'Velasquez', 'Si',
            'Estrada', 'Abu', 'Green', 'Scott', 'Roberts', 'Rivas', 'Isah', 'Escobar', 'Lou', 'Duran', 'Dinh', 'Dey',
            'Tadesse', 'Nisha', 'Benitez', 'Cortes', 'More', 'Lawal', 'Kuang', 'Dao', 'Kwon', 'Abebe', 'Mahamat',
            'Evans', 'Kamara', 'Campbell', 'Mir', 'Girma', 'Che', 'Win', 'Khalid', 'Nong', 'Borges', 'Lim', 'Yakubu',
            'Pierre', 'Jassim', 'Diop', 'Reddy', 'Quispe', 'Gayakwad', 'Sinha', 'Yousef', 'de La Cruz', 'Lara', 'Hill',
            'Valencia', 'Shaw', 'Felix', 'Taha', 'Rasool', 'Aguirre', 'Aminu', 'Sadiq', 'Maldonado', 'Vasav', 'Omer',
            'Calderon', 'Nelson', 'Wong', 'Valdez', 'Karmakar', 'Baker', 'Parveen', 'Koffi', 'Rahim', 'Correia', 'Guerra',
            'Trinh', 'Varma', 'Arif', 'Gonçalves', 'Jana', 'Jian', 'George', 'Vera', 'Xi', 'Demir', 'Cardenas', 'Mun',
            'Sosa', 'Kouassi', 'Haider', 'Serrano', 'Schneider', 'Bag', 'Lang', 'Meyer', 'Parvin', 'Ly', 'Figueroa', 'Hadi',
            'Magar', 'Villanueva', 'Padilla', 'Ju', 'Ayala', 'Sih', 'Nasser', 'Edwards', 'Pineda', 'Rosales', 'Quan', 'Zin',
            'Hosseini', 'Kadam', 'Blanco', 'Mansour', 'Barik', 'Rahaman', 'Sasaki', 'Ramzan', 'Oraon', 'Hayat', 'San', 'Dembele',
            'Brito', 'Carrillo', 'Babu', 'Rong', 'Mitchell', 'Tudu', 'De', 'Al Numan', 'Sunday', 'Velazquez', 'Matsumoto',
            'Michael', 'Amir', 'Setiawan', 'Khalaf', 'Adhikari', 'Jan', 'de Araujo', 'Tiwari', 'Khine', 'Javed', 'Camacho',
            'Eze', 'Sántos', 'Bhagat', 'Morris', 'Gil', 'Mohsen', 'Sylla', 'Yamaguchi', 'Latif', 'Mal', 'Sarker', 'Mahdi',
            'Salisu', 'Konate', 'Rasheed', 'Elias', 'Mamani', 'Sidibe', 'Turner', 'Phillips', 'Raza', 'Swain', 'Kebede',
            'Yousuf', 'Zhuo', 'Solis', 'Carter', 'Mali', 'Sing', 'Mori', 'Murphy', 'Nasir', 'Inoue', 'Kouadio', 'Anh',
            'Mallik', 'Salas', 'Bravo', 'de Carvalho', 'Parra', 'Paek', 'Stewart', 'Tavares', 'Ti', 'Afzal', 'Sekha',
            'Kanwar', 'Verma', 'Henrique', 'Kouame', 'Collins', 'Cooper', 'Bo', 'António', 'Quintero', 'Bekele', 'Ahmadi',
            'Nair', 'Kelly', 'Nahar', 'Pinheiro', 'Çelik', 'Bux', 'Adel', 'Şahin', 'Wagner', 'dela Cruz', 'Akpan',
            'Weber', 'Dube', 'Phyo', 'Salam', 'Gamal', 'Asif', 'Morgan', 'Van', 'Leng', 'Luong', 'Yıldız', 'Sheik',
            'Hnin', 'Barros', 'Pedro', 'Palacios', 'Parker', 'Na', 'Abe', 'Kimura', 'Bezerra', 'Cortez', 'Doan',
            'Shehu', 'Bahadur', 'Joshi', 'Mane', 'Yıldırım', 'Hidayat', 'Farah', 'Nam', 'Ahamed', 'Barrios', 'Balde',
            'Amadi', 'Bera', 'Bell', 'Miya', 'Nabi', 'Gabriel', 'Hamad', 'Shankar', 'Sen', 'Lucas', 'Basumatary', 'Fischer',
            'Robles', 'Dei', 'Arshad', 'Hailu', 'Kouakou', 'Farooq', 'Oumarou', 'Fofana', 'Jamal', 'Hansen', 'Wood',
            'Aden', 'Pires', 'Alemayehu', 'Peralta', 'Espinosa', 'Dlamini', 'Wati', 'Meza', 'Hayashi', 'Petrov',
            'Hamed', 'Shimizu', 'Lestari', 'Mensah', 'Jang', 'Panda', 'Moses', 'Saidi', 'Cen', 'Tahir', 'Sahani',
            'Miguel', 'Halder', 'Cook', 'Moyo', 'Watson', 'Hughes', 'Ochoa', 'Paredes', 'Mahmood', 'Lozano', 'Hameed',
            'Conde', 'Otieno', 'Fatima', 'Mousa', 'Bhoi', 'Rogers', 'Than', 'Xavier', 'Guevara', 'Osorio', 'Thin', 'Ward',
            'Sangma', 'Salinas', 'Fonseca', 'Riaz', 'Valenzuela', 'Sulaiman', 'Ao', 'Thanh', 'Öztürk', 'Alonso', 'da Cruz', 'Yahya',
            'Gou', 'Gogoi', 'Saputra', 'Pramanik', 'Zapata', 'Younis', 'Maseeh', 'Roman', 'Fei', 'Francis', 'Mukherjee', 'Manna',
            'Aydın', 'Freitas', 'Sha', 'Richard', 'Sui', 'Leal', 'Vaghel', 'Shahzad', 'Abbasi', 'Petrova', 'Ndlovu', 'Bailey',
            'Shafi', 'Orozco', 'Banerjee', 'Ponce', 'Zamora', 'Sahoo', 'Kale', 'Banza', 'Soe', 'Coelho', 'Amadou', 'Bagdi',
            'Adamou', 'Narayan', 'Rathav', 'Ono', 'Ibarra', 'Tun', 'Caballero', 'Umaru', 'Mercado', 'Bennett', 'Montoya',
            'Yar', 'Aquino', 'Barrera',
        ];
    }
}
