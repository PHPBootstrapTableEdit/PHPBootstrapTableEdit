
/* sqlite3 example.db < example.sql */

    create table if not exists markets(
        id integer primary key autoincrement, 
        title text,
        photo blob,
        email text,
        password text, 
        country text,
        province text,
        notes text,
        is_active int,
        create_date text
    );

    create table if not exists countries(
        id integer primary key autoincrement,
        code text,    
        title text
    );

    create table if not exists provinces(
        id integer primary key autoincrement,
        country text,
        code text,    
        title text
    );


/* sample data - any database */

    insert into markets(title, email, country, is_active, create_date, notes) values 
    ('Market 1', 'joey@example.net',    'CA', 1,    '2023-01-01', 'notes 123'),
    ('Market 2', 'deedee@example.net',  'US', 1,    '2023-02-02', 'these are notes'),
    ('Market 3', 'johnny@example.net',  'US', 1,    '2023-03-03', 'notes again'),
    ('Market 4', 'marky@example.net',   'MX', 1,    '2023-03-03', 'more notes');

    insert into countries(code, title) values ('CA', 'Canada'), ('US', 'United States'), ('MX', 'Mexico');
    insert into provinces(country, title, code) values ('US', 'Alabama', 'AL'), ('US', 'Alaska', 'AK'), ('US', 'Arizona', 'AZ'), ('US', 'Arkansas', 'AR'), ('US', 'California', 'CA'), ('US', 'Colorado', 'CO'), ('US', 'Connecticut', 'CT'), ('US', 'Delaware', 'DE'), ('US', 'District of Columbia', 'DC'), ('US', 'Florida', 'FL'), ('US', 'Georgia', 'GA'), ('US', 'Hawaii', 'HI'), ('US', 'Idaho', 'ID'), ('US', 'Illinois', 'IL'), ('US', 'Indiana', 'IN'), ('US', 'Iowa', 'IA'), ('US', 'Kansas', 'KS'), ('US', 'Kentucky', 'KY'), ('US', 'Louisiana', 'LA'), ('US', 'Maine', 'ME'), ('US', 'Maryland', 'MD'), ('US', 'Massachusetts', 'MA'), ('US', 'Michigan', 'MI'), ('US', 'Minnesota', 'MN'), ('US', 'Mississippi', 'MS'), ('US', 'Missouri', 'MO'), ('US', 'Montana', 'MT'), ('US', 'Nebraska', 'NE'), ('US', 'Nevada', 'NV'), ('US', 'New Hampshire', 'NH'), ('US', 'New Jersey', 'NJ'), ('US', 'New Mexico', 'NM'), ('US', 'New York', 'NY'), ('US', 'North Carolina', 'NC'), ('US', 'North Dakota', 'ND'), ('US', 'Ohio', 'OH'), ('US', 'Oklahoma', 'OK'), ('US', 'Oregon', 'OR'), ('US', 'Pennsylvania', 'PA'), ('US', 'Rhode Island', 'RI'), ('US', 'South Carolina', 'SC'), ('US', 'South Dakota', 'SD'), ('US', 'Tennessee', 'TN'), ('US', 'Texas', 'TX'), ('US', 'Utah', 'UT'), ('US', 'Vermont', 'VT'), ('US', 'Virginia', 'VA'), ('US', 'Washington', 'WA'), ('US', 'West Virginia', 'WV'), ('US', 'Wisconsin', 'WI'), ('US', 'Wyoming', 'WY'), ('CA', 'Alberta', 'AB'), ('CA', 'British Columbia', 'BC'), ('CA', 'Manitoba', 'MB'), ('CA', 'New Brunswick', 'NB'), ('CA', 'Newfoundland and Labrador', 'NL'), ('CA', 'Northwest Territories', 'NT'), ('CA', 'Nova Scotia', 'NS'), ('CA', 'Nunavut', 'NU'), ('CA', 'Ontario', 'ON'), ('CA', 'Prince Edward Island', 'PE'), ('CA', 'Quebec', 'QC'), ('CA', 'Saskatchewan', 'SK'), ('CA', 'Yukon', 'YT'), ('MX', 'Aguascalientes', 'AG'), ('MX', 'Baja California Norte', 'BN'), ('MX', 'Baja California Sur', 'BS'), ('MX', 'Coahuila', 'CH'), ('MX', 'Chihuahua', 'CI'), ('MX', 'Colima', 'CL'), ('MX', 'Campeche', 'CP'), ('MX', 'Chiapas', 'CS'), ('MX', 'Districto Federal', 'DF'), ('MX', 'Durango', 'DG'), ('MX', 'Guerrero', 'GE'), ('MX', 'Guanajuato', 'GJ'), ('MX', 'Hidalgo', 'HD'), ('MX', 'Jalisco', 'JA'), ('MX', 'Michoacan', 'MC'), ('MX', 'Morelos', 'MR'), ('MX', 'Mexico', 'MX'), ('MX', 'Nayarit', 'NA'), ('MX', 'Nuevo Leon', 'NL'), ('MX', 'Oaxaca', 'OA'), ('MX', 'Puebla', 'PU'), ('MX', 'Queretaro', 'QE'), ('MX', 'Quintana Roo', 'QI'), ('MX', 'Sinaloa', 'SI'), ('MX', 'San Luis Potosi', 'SL'), ('MX', 'Sonora', 'SO'), ('MX', 'Tamaulipas', 'TA'), ('MX', 'Tabasco', 'TB'), ('MX', 'Tlaxcala', 'TL'), ('MX', 'Veracruz', 'VC'), ('MX', 'Yucatan', 'YU'), ('MX', 'Zacatecas', 'ZA');

/*
PostgreSQL

    # create db and user
    sudo -u postgres psql
    create database example;
    create user example with encrypted password 'example';
    grant all privileges on database example to example;

    # test connection
    psql -d example -U example -W -h 127.0.0.1

    create table markets(
        id serial primary key,
        title varchar(255),
        photo text,
        email varchar(255),
        country varchar(2),
        is_active int,
        create_date timestamp,
        notes text
    );

    create table if not exists countries(
        id serial primary key,
        code  varchar(2),
        title varchar(255)
    );

    create table if not exists provinces(
        id serial primary key,
        country varchar(2),
        code  varchar(2),
        title varchar(255)
    );


MariaDB/MySQL

    # create db and user
    sudo mysql
    create database if not exists example;
    use example;

    create user 'example'@'localhost' identified by 'example';
    grant all privileges on example.* to example@localhost;
    flush privileges;
    exit;

    # test connection with new user
    mysql -D example -u example -p

    create table if not exists markets(
        id int unsigned not null auto_increment primary key,
        title varchar(255),
        photo text,
        email varchar(255),
        country varchar(2),
        is_active tinyint(1),
        create_date date,
        notes text
    );

    create table if not exists countries(
        id int unsigned not null auto_increment primary key, 
        code varchar(2),
        title varchar(255)
    );

    create table if not exists provinces(
        id int unsigned not null auto_increment primary key, 
        country varchar(2),
        code varchar(2),
        title varchar(255)
    );

*/
