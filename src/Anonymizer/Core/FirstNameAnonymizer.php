<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractEnumAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'firstname',
    pack: 'core',
    description: <<<TXT
    Anonymize with a random firstname from a sample of ~1000 items.
    TXT
)]
class FirstNameAnonymizer extends AbstractEnumAnonymizer
{
    /**
     * {@inheritdoc}
     */
    protected function getSample(): array
    {
        return [
            'Maria', 'Nushi', 'Mohammed', 'Jose', 'Muhammad', 'Mohamed', 'Wei', 'Mohammad',
            'Ahmed', 'Yan', 'Ali', 'John', 'David', 'Li', 'Abdul', 'Ana', 'Ying', 'Michael', 'Juan',
            'Anna', 'Mary', 'Jean', 'Robert', 'Daniel', 'Luis', 'Carlos', 'James', 'Antonio', 'Joseph',
            'Hui', 'Elena', 'Francisco', 'Hong', 'Marie', 'Min', 'Lei', 'Yu', 'Ibrahim', 'Peter',
            'Fatima', 'Aleksandr', 'Richard', 'Xin', 'Bin', 'Paul', 'Ping', 'Lin', 'Olga', 'Sri',
            'Pedro', 'William', 'Rosa', 'Thomas', 'Jorge', 'Yong', 'Elizabeth', 'Sergey', 'Ram',
            'Patricia', 'Hassan', 'Anita', 'Manuel', 'Victor', 'Sandra', 'Ming', 'Siti', 'Miguel',
            'Emmanuel', 'Samuel', 'Ling', 'Charles', 'Sarah', 'Mario', 'Joao', 'Tatyana', 'Mark',
            'Rita', 'Martin', 'Svetlana', 'Patrick', 'Natalya', 'Qing', 'Ahmad', 'Martha', 'Andrey',
            'Sunita', 'Andrea', 'Christine', 'Irina', 'Laura', 'Linda', 'Marina', 'Carmen', 'Ghulam',
            'Vladimir', 'Barbara', 'Angela', 'George', 'Roberto', 'Peng', 'Ivan', 'Alexander',
            'Ekaterina', 'Qiang', 'Yun', 'Jesus', 'Susan', 'Sara', 'Noor', 'Mariam', 'Dmitriy',
            'Eric', 'Zahra', 'Fatma', 'Fernando', 'Esther', 'Jin', 'Diana', 'Mahmoud', 'Chao', 'Rong',
            'Santosh', 'Nancy', 'Musa', 'Anh', 'Omar', 'Jennifer', 'Gang', 'Yue', 'Claudia', 'Maryam',
            'Gloria', 'Ruth', 'Teresa', 'Sanjay', 'Na', 'Nur', 'Kyaw', 'Francis', 'Amina', 'Denis',
            'Stephen', 'Sunil', 'Gabriel', 'Andrew', 'Eduardo', 'Abdullah', 'Grace', 'Anastasiya',
            'Mei', 'Rafael', 'Ricardo', 'Christian', 'Aleksey', 'Steven', 'Gita', 'Frank', 'Jianhua',
            'Mo', 'Karen', 'Masmaat', 'Brian', 'Christopher', 'Xiaoyan', 'Rajesh', 'Mustafa', 'Eva',
            'Bibi', 'Monica', 'Oscar', 'Andre', 'Catherine', 'Kai', 'Ramesh', 'Liping', 'Sonia', 'Anthony',
            'Mina', 'Manoj', 'Ashok', 'Rose', 'Alberto', 'Ning', 'Rekha', 'Chen', 'Lan', 'Aung', 'Alex',
            'Suresh', 'Anil', 'Fatemeh', 'Julio', 'Zhen', 'Simon', 'Paulo', 'Juana', 'Irene', 'Adam',
            'Kevin', 'Vijay', 'Syed', 'Mehmet', 'Angel', 'Edward', 'Julia', 'Victoria', 'Ronald', 'Cheng',
            'Lakshmi', 'Francisca', 'Veronica', 'Roman', 'Ismail', 'Margaret', 'Luz', 'Anne', 'Silvia',
            'Kamal', 'Raju', 'Sergio', 'Walter', 'Lisa', 'Marta', 'Nadezhda', 'Marco', 'Albert', 'Alice',
            'Asha', 'Xiang', 'Isabel', 'Zainab', 'Michelle', 'Long', 'Michel', 'Pierre', 'Saleh', 'Haiyan',
            'Felix', 'Salma', 'Hector', 'Manju', 'Jan', 'Roger', 'Joyce', 'Margarita', 'Joel', 'Jessica',
            'Lucia', 'Pavel', 'Hai', 'Nadia', 'Mariya', 'Jianping', 'Jacqueline', 'Halima', 'Nan', 'Rama',
            'Benjamin', 'Rebecca', 'Julie', 'Vera', 'Vinod', 'Kun', 'Khalid', 'Ramon', 'Janet', 'Sharon',
            'Suman', 'Jane', 'Lihua', 'Shanti', 'Abubakar', 'Aisha', 'Zaw', 'Jonathan', 'Paula', 'Bruno',
            'Monika', 'Maksim', 'Mamadou', 'Judith', 'Kenneth', 'Mostafa', 'Chris', 'Helen', 'Nikolay',
            'Rina', 'Zhiqiang', 'Marcos', 'Mária', 'Norma', 'Anton', 'Raul', 'Cristina', 'Xiaohong', 'Henry',
            'Wai', 'Antonia', 'Betty', 'Alejandro', 'Nelson', 'Igor', 'Evgeniy', 'Adriana', 'Amir', 'Pablo',
            'Raj', 'Regina', 'Rajendra', 'Brenda', 'Linh', 'Sani', 'Hussein', 'Gul', 'Mikhail', 'Jaime',
            'Nicole', 'Sima', 'Giuseppe', 'Dinesh', 'Tatiana', 'Bernard', 'Gary', 'Lijun', 'Sita', 'Javier',
            'Shan', 'Hasan', 'Yuliya', 'Ni', 'Moses', 'Agnes', 'Cesar', 'Xiaoli', 'Usha', 'Alfredo', 'Meng',
            'Jianguo', 'Kiran', 'Dennis', 'Khaled', 'Carol', 'Rani', 'Yusuf', 'Xiaoping', 'Ha', 'Rakesh',
            'Isaac', 'Luiz', 'Josephine', 'Krishna', 'Mohamad', 'Raymond', 'Erika', 'Blanca', 'Jianjun',
            'Deborah', 'Amanda', 'Natalia', 'Gladys', 'Florence', 'Asma', 'Usman', 'Donald', 'Lijuan', 'Zhi',
            'Abdullahi', 'Stephanie', 'Tingting', 'Saeed', 'Edgar', 'Maya', 'Han', 'Mahdi', 'Khadija',
            'Valentina', 'Ruben', 'Tuan', 'Thanh', 'Jason', 'Ei', 'Doris', 'Fatoumata', 'Darya', 'Rene',
            'Cecilia', 'Umar', 'Cynthia', 'Gustavo', 'Kim', 'Lucas', 'Zin', 'Xuan', 'Abdo', 'Moussa', 'Amit',
            'Mona', 'Xiaoling', 'Dilip', 'Caroline', 'An', 'Tun', 'Muhammed', 'Claude', 'Elisabeth', 'Yuanyuan',
            'Beatrice', 'Edwin', 'Xiaodong', 'Hung', 'Kristina', 'Scott', 'Christina', 'Ajay', 'Alina',
            'Denise', 'Matthew', 'Vladymyr', 'Daniela', 'Pushpa', 'Joan', 'Leonardo', 'Aleksandra', 'Ravi',
            'Virginia', 'Hamid', 'Alain', 'António', 'Lyubov', 'Xiaoming', 'Alicia', 'Mohan', 'Hans', 'Xing',
            'Ann', 'Laoshi', 'Santos', 'Di', 'Said', 'Haji', 'Nicolas', 'Felipe', 'Amal', 'Bekele', 'Donna',
            'Dina', 'Hugo', 'Yolanda', 'Laxmi', 'Munni', 'Maryia', 'Beatriz', 'Urmila', 'Mukesh', 'Brigitte',
            'Radha', 'Evelyn', 'Emma', 'Kenji', 'Galina', 'Diego', 'Viktor', 'Arun', 'Alexandra', 'Alfred',
            'Chun', 'Huan', 'Nykolai', 'Louis', 'Armando', 'Sunday', 'Vincent', 'Edith', 'Jingjing', 'Samira',
            'Zhiyong', 'Alan', 'Hiroshi', 'Gabriela', 'Savitri', 'Rachel', 'Adrian', 'Mira', 'Shankar', 'Carla',
            'Miriam', 'Gopal', 'Yanping', 'Lyudmila', 'Lalita', 'Magdalena', 'Xiaohua', 'Anwar', 'Sushila',
            'Jianming', 'Amy', 'Mercy', 'Timothy', 'Irma', 'Xiaofeng', 'Marcelo', 'Abdel', 'Karim', 'Rodrigo',
            'Pamela', 'Sangita', 'Agus', 'Weidong', 'Jerry', 'Jacques', 'Jeanne', 'Joy', 'Ganesh', 'Ingrid',
            'Nirmala', 'Sumitra', 'Juliana', 'Mahesh', 'Nina', 'Xiaojun', 'Viktoriya', 'Rahul', 'Petra', 'Zhiming',
            'Nikita', 'Shuang', 'Yasmin', 'Chi', 'Yin', 'Qiong', 'Ayşe', 'Phuong', 'Melissa', 'Quan', 'Wilson',
            'Trang', 'Jeffrey', 'Giovanni', 'Larry', 'Hang', 'Elias', 'Zhigang', 'Adama', 'Jamila', 'Kelly', 'Osman',
            'Piotr', 'Savita', 'Xiaoying', 'Philip', 'Oksana', 'Raja', 'Dorothy', 'Zhiwei', 'Sultan', 'Ernesto',
            'Jianfeng', 'Xiaohui', 'Xiaomei', 'Oleg', 'Joe', 'Ruslan', 'Shu', 'Diane', 'Andres', 'Song', 'Shirley',
            'Hongmei', 'Adamu', 'Dung', 'Manoel', 'Xuemei', 'Justin', 'Shiv', 'Enrique', 'Mariana', 'Serhei', 'Monique',
            'Vanessa', 'Prakash', 'Jitendra', 'Dan', 'Dominique', 'Susana', 'Annie', 'Douglas', 'Saroj', 'Ahmet',
            'Bashir', 'Elsa', 'Samir', 'Abbas', 'Aya', 'Sarita', 'Chunyan', 'Lidia', 'Guillermo', 'Jinhua', 'Luisa',
            'Mai', 'Thu', 'Karin', 'Hongwei', 'Andreas', 'Leila', 'Weiwei', 'Man', 'Helena', 'Philippe', 'Vicente',
            'Dongmei', 'Tong', 'Konstantin', 'Tania', 'Pascal', 'Aziz', 'Martina', 'Fred', 'Tamara', 'Tony', 'Naseem',
            'Ryan', 'Lucy', 'Surendra', 'Jyoti', 'Pauline', 'Marc', 'Zhihua', 'Sabina', 'Guadalupe', 'Salim', 'Amar',
            'Lydia', 'Mahendra', 'Joshua', 'Guoqiang', 'Lee', 'Seyyed', 'Ayesha', 'Muhamad', 'Karina', 'Salah', 'Ilya',
            'Josef', 'Leticia', 'O', 'Aicha', 'Michele', 'Nasir', 'Sadia', 'Josefa', 'Narayan', 'Kavita', 'Pramod',
            'Pa', 'Sofia', 'Hari', 'Alexey', 'Blessing', 'Hossein', 'Tina', 'Claudio', 'Nathalie', 'Arthur', 'Hongyan',
            'Xiaoyu', 'Sam', 'Karl', 'Mamta', 'Mercedes', 'Shigeru', 'Kathleen', 'Farida', 'Hawa', 'Sakina', 'Jianxin',
            'Marcel', 'Yvan', 'Guohua', 'Myat', 'Emine', 'Tara', 'Francesco', 'Nurul', 'Nana', 'Sayed', 'Jay', 'Abraham',
            'Nour', 'Imran', 'Sai', 'Iman', 'Lwin', 'Jamal', 'Thao', 'Wolfgang', 'Nam', 'Manuela', 'Jianzhong', 'Raquel',
            'Artur', 'Uma', 'Louise', 'A', 'Nabil', 'Hilda', 'Punam', 'Abdoulaye', 'Wendy', 'Ian', 'Stella', 'Elvira',
            'Valerie', 'Eman', 'Subhash', 'Sylvia', 'Jeff', 'Carolina', 'Olha', 'Tomasz', 'Masoumeh', 'Zhijun', 'Anastasia',
            'Pradip', 'Tadesse', 'Andrei', 'Adel', 'Werner', 'Ursula', 'Clara', 'Lina', 'Charlotte', 'Angelina', 'Cong',
            'Tomas', 'Jacob', 'Yanling', 'Gilbert', 'Gerald', 'Le', 'Zhihong', 'Jim', 'Valentyna', 'Huy', 'Hamza',
            'Shanshan', 'Om', 'Than', 'Lilian', 'Francois', 'Rodolfo', 'Melanie', 'Dipak', 'Marlene', 'Ashraf', 'Gerardo',
            'Sheila', 'Rana', 'Weihua', 'Kalpana', 'Simone', 'Orlando', 'Petr', 'Marwa', 'Arif', 'Eunice', 'Farzana',
            'Parvati', 'Angelo', 'Amadou', 'Robin', 'Rashid', 'Van', 'Ma', 'Abel', 'Ranjit', 'Alexandre', 'Jack', 'Yuhua',
            'Madina', 'Kamla', 'Fabio', 'Mariama', 'Liming', 'Ngoc', 'Prem', 'Mustapha', 'Sabine', 'Wenjun', 'Ka', 'Aida',
            'Yanhong', 'Lihong', 'Qun', 'Klaus', 'Junjie', 'Ran', 'Heba', 'Shah', 'Son', 'Sharmin', 'Minh', 'Terry',
            'Yvonne', 'Jianmin', 'Lawrence', 'Thuy', 'Lal', 'Habiba', 'Therese', 'Jenny', 'Mike', 'Nada', 'Xiaolin',
            'Vasylyi', 'Manfred', 'Marcia', 'Shobha', 'Tian', 'Keith', 'Guy', 'Umesh', 'Solomon', 'Asmaa', 'Jimmy',
            'Paulina', 'Aminata', 'Nora', 'Ravindra', 'Sophie', 'Joanna', 'Weimin', 'Yanhua', 'Sylvie', 'Xiaoqing',
            'Jianwei', 'Sachiko', 'Raimundo', 'Laila', 'Pankaj', 'Reza', 'Roland', 'Emily', 'Habib', 'Smt', 'Mohsen',
            'Angelica', 'Liliana', 'Phyo', 'Hatice', 'Yingying', 'Ta', 'Lyudmyla', 'Isabelle', 'José', 'Tim', 'Durga',
            'Naresh', 'Babu', 'Wenjie', 'Nguyen', 'Arjun', 'Shyam', 'Alaa', 'Herbert', 'Olivier', 'Haibo', 'Kseniya',
            'Hanan', 'Amin', 'Renu', 'Masako', 'Xian', 'Priyanka', 'Weiping', 'Nasreen', 'Salvador', 'Martine', 'Judy',
            'Maha', 'Basanti', 'Nicholas', 'Theresa', 'Nusrat', 'Shahid', 'Stefan', 'Lingling', 'Marcin', 'Sebastian',
            'Josefina', 'Gilberto', 'Ai', 'Ida', 'Huimin', 'Artyom', 'Shakuntala', 'Samina', 'Rosario', 'Qinghua', 'Roy',
            'Kassa', 'Pramila', 'Kathy', 'Rabia', 'Hoa', 'Nestor', 'Katsumi', 'Paola', 'Ernest', 'Yuriy', 'Yousef', 'Lixin',
            'Zhihui', 'Sheikh', 'Kimberly', 'Luciano', 'Krzysztof', 'Hoang', 'Faisal', 'Dmitry', 'Alma', 'Aliyu', 'Yanyan',
            'Chunhua', 'Xiaomin', 'Hieu', 'Yoko', 'Dolores', 'Leonard', 'Xiaowei', 'Weiming', 'Marilyn', 'Isa', 'Bharat',
            'Katarzyna', 'Shila', 'Sabrina', 'Arturo', 'Nga', 'Dora', 'Gerhard', 'Haiying', 'Cristian', 'Laksmi', 'Pei',
            'Nasrin', 'Kamala', 'Joaquim', 'Julius', 'Saraswati', 'Ganga', 'Chandra', 'Maurice', 'Tien', 'Kirill',
            'Rosemary', 'Yen', 'Elaine', 'Marianne', 'Ca', 'Cheryl', 'Hana', 'Helga', 'Wenjing', 'Zhenhua', 'Liying',
            'Faith', 'Heather', 'Tu', 'Mi', 'Heinz', 'Halyna', 'Zhijian', 'Sandeep', 'Satish', 'Ellen', 'Haitao',
            'Sangeeta', 'Bernadette', 'Noel', 'Guoliang', 'Huong', 'Deepak', 'Christophe', 'Ken', 'Zhiping', 'Kailash',
            'Lorena', 'Samia', 'Yumei', 'Issa', 'Gregory', 'Lila', 'Yuping', 'Chantal', 'Thierry', 'Xiaoxia', 'Jianhui',
            'Rustam', 'Ester',
        ];
    }
}
