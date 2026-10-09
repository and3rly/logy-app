<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Catálogos que carga la pantalla de instalación (controllers/Instalacion.php).
| Los ids se conservan: el código usa constantes para los estados y códigos para los movimientos.
| Las tablas "Por empresa" reciben el empresa_id de la empresa que se instala.
*/
$config["instalacion"] = [
	# Geografía (globales)
	"pais" => [
		[
			"id" => 1,
			"codigo" => "GT",
			"nombre" => "Guatemala",
			"activo" => 1
		]
	],

	# Departamentos de Guatemala
	"departamento" => [
		[
			"id" => 2,
			"codigo" => "GT-AV",
			"nombre" => "Alta Verapaz",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 3,
			"codigo" => "GT-BV",
			"nombre" => "Baja Verapaz",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 4,
			"codigo" => "GT-CM",
			"nombre" => "Chimaltenango",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 5,
			"codigo" => "GT-CQ",
			"nombre" => "Chiquimula",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 6,
			"codigo" => "GT-PR",
			"nombre" => "El Progreso",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 7,
			"codigo" => "GT-ES",
			"nombre" => "Escuintla",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 8,
			"codigo" => "GT-GU",
			"nombre" => "Guatemala",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 9,
			"codigo" => "GT-HU",
			"nombre" => "Huehuetenango",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 10,
			"codigo" => "GT-IZ",
			"nombre" => "Izabal",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 11,
			"codigo" => "GT-JA",
			"nombre" => "Jalapa",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 12,
			"codigo" => "GT-JU",
			"nombre" => "Jutiapa",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 13,
			"codigo" => "GT-PE",
			"nombre" => "Petén",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 14,
			"codigo" => "GT-QZ",
			"nombre" => "Quetzaltenango",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 15,
			"codigo" => "GT-QI",
			"nombre" => "Quiché",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 16,
			"codigo" => "GT-RE",
			"nombre" => "Retalhuleu",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 17,
			"codigo" => "GT-SC",
			"nombre" => "Sacatepéquez",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 18,
			"codigo" => "GT-SM",
			"nombre" => "San Marcos",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 19,
			"codigo" => "GT-SO",
			"nombre" => "Santa Rosa",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 20,
			"codigo" => "GT-SL",
			"nombre" => "Sololá",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 21,
			"codigo" => "GT-SZ",
			"nombre" => "Suchitepéquez",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 22,
			"codigo" => "GT-TC",
			"nombre" => "Totonicapán",
			"activo" => 1,
			"pais_id" => 1
		],
		[
			"id" => 23,
			"codigo" => "GT-ZA",
			"nombre" => "Zacapa",
			"activo" => 1,
			"pais_id" => 1
		]
	],

	# Municipios de Guatemala
	"municipio" => [
		[
			"id" => 2,
			"codigo" => 1601,
			"nombre" => "Chahal",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 3,
			"codigo" => 1602,
			"nombre" => "Chisec",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 4,
			"codigo" => 1603,
			"nombre" => "Cobán",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 5,
			"codigo" => 1604,
			"nombre" => "Fray Bartolomé de las Casas",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 6,
			"codigo" => 1605,
			"nombre" => "Lanquín",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 7,
			"codigo" => 1606,
			"nombre" => "Panzós",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 8,
			"codigo" => 1607,
			"nombre" => "Raxruha",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 9,
			"codigo" => 1608,
			"nombre" => "San Cristóbal Verapaz",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 10,
			"codigo" => 1609,
			"nombre" => "San Juan Chamelco",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 11,
			"codigo" => 1610,
			"nombre" => "San Pedro Carchá",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 12,
			"codigo" => 1611,
			"nombre" => "Santa Cruz Verapaz",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 13,
			"codigo" => 1612,
			"nombre" => "Senahú",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 14,
			"codigo" => 1613,
			"nombre" => "Tactic",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 15,
			"codigo" => 1614,
			"nombre" => "Tamahú",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 16,
			"codigo" => 1615,
			"nombre" => "Tucurú",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 17,
			"codigo" => 1616,
			"nombre" => "Santa María Cahabón",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 18,
			"codigo" => 1617,
			"nombre" => "Santa Catarina La Tinta",
			"activo" => 1,
			"departamento_id" => 2
		],
		[
			"id" => 19,
			"codigo" => 1501,
			"nombre" => "Cubulco",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 20,
			"codigo" => 1502,
			"nombre" => "Granados",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 21,
			"codigo" => 1503,
			"nombre" => "Purulhá",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 22,
			"codigo" => 1504,
			"nombre" => "Rabinal",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 23,
			"codigo" => 1505,
			"nombre" => "Salamá",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 24,
			"codigo" => 1506,
			"nombre" => "San Jerónimo",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 25,
			"codigo" => 1507,
			"nombre" => "San Miguel Chicaj",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 26,
			"codigo" => 1508,
			"nombre" => "Santa Cruz El Chol",
			"activo" => 1,
			"departamento_id" => 3
		],
		[
			"id" => 27,
			"codigo" => "0401",
			"nombre" => "Acatenango",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 28,
			"codigo" => "0402",
			"nombre" => "Chimaltenango",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 29,
			"codigo" => "0403",
			"nombre" => "El Tejar",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 30,
			"codigo" => "0404",
			"nombre" => "Parramos",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 31,
			"codigo" => "0405",
			"nombre" => "Patzicía",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 32,
			"codigo" => "0406",
			"nombre" => "Patzún",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 33,
			"codigo" => "0407",
			"nombre" => "Pochuta",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 34,
			"codigo" => "0408",
			"nombre" => "San Andrés Itzapa",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 35,
			"codigo" => "0409",
			"nombre" => "San José Poaquil",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 36,
			"codigo" => "0410",
			"nombre" => "San Juan Comalapa",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 37,
			"codigo" => "0411",
			"nombre" => "San Martín Jilotepeque",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 38,
			"codigo" => "0412",
			"nombre" => "Santa Apolonia",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 39,
			"codigo" => "0413",
			"nombre" => "Santa Cruz Balanyá",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 40,
			"codigo" => "0414",
			"nombre" => "Tecpán Guatemala",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 41,
			"codigo" => "0415",
			"nombre" => "Yepocapa",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 42,
			"codigo" => "0416",
			"nombre" => "Zaragoza",
			"activo" => 1,
			"departamento_id" => 4
		],
		[
			"id" => 43,
			"codigo" => 2001,
			"nombre" => "Camotán",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 44,
			"codigo" => 2002,
			"nombre" => "Chiquimula",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 45,
			"codigo" => 2003,
			"nombre" => "Concepción Las Minas",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 46,
			"codigo" => 2004,
			"nombre" => "Esquipulas",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 47,
			"codigo" => 2005,
			"nombre" => "Ipala",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 48,
			"codigo" => 2006,
			"nombre" => "Jocotán",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 49,
			"codigo" => 2007,
			"nombre" => "Olopa",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 50,
			"codigo" => 2008,
			"nombre" => "Quezaltepeque",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 51,
			"codigo" => 2009,
			"nombre" => "San Jacinto",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 52,
			"codigo" => 2010,
			"nombre" => "San José La Arada",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 53,
			"codigo" => 2011,
			"nombre" => "San Juan Ermita",
			"activo" => 1,
			"departamento_id" => 5
		],
		[
			"id" => 54,
			"codigo" => "0201",
			"nombre" => "El Jícaro",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 55,
			"codigo" => "0202",
			"nombre" => "Guastatoya",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 56,
			"codigo" => "0203",
			"nombre" => "Morazán",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 57,
			"codigo" => "0204",
			"nombre" => "San Agustín Acasaguastlán",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 58,
			"codigo" => "0205",
			"nombre" => "San Antonio La Paz",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 59,
			"codigo" => "0206",
			"nombre" => "San Cristóbal Acasaguastlán",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 60,
			"codigo" => "0207",
			"nombre" => "Sanarate",
			"activo" => 1,
			"departamento_id" => 6
		],
		[
			"id" => 61,
			"codigo" => "0501",
			"nombre" => "Escuintla",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 62,
			"codigo" => "0502",
			"nombre" => "Guanagazapa",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 63,
			"codigo" => "0503",
			"nombre" => "Iztapa",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 64,
			"codigo" => "0504",
			"nombre" => "La Democracia",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 65,
			"codigo" => "0505",
			"nombre" => "La Gomera",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 66,
			"codigo" => "0506",
			"nombre" => "Masagua",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 67,
			"codigo" => "0507",
			"nombre" => "Nueva Concepción",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 68,
			"codigo" => "0508",
			"nombre" => "Palín",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 69,
			"codigo" => "0509",
			"nombre" => "San José",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 70,
			"codigo" => "0510",
			"nombre" => "San Vicente Pacaya",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 71,
			"codigo" => "0511",
			"nombre" => "Santa Lucía Cotzumalguapa",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 72,
			"codigo" => "0512",
			"nombre" => "Siquinalá",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 73,
			"codigo" => "0513",
			"nombre" => "Tiquisate",
			"activo" => 1,
			"departamento_id" => 7
		],
		[
			"id" => 74,
			"codigo" => "0101",
			"nombre" => "Amatitlán",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 75,
			"codigo" => "0102",
			"nombre" => "Chinautla",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 76,
			"codigo" => "0103",
			"nombre" => "Chuarrancho",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 77,
			"codigo" => "0104",
			"nombre" => "Fraijanes",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 78,
			"codigo" => "0105",
			"nombre" => "Guatemala City",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 79,
			"codigo" => "0106",
			"nombre" => "Mixco",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 80,
			"codigo" => "0107",
			"nombre" => "Palencia",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 81,
			"codigo" => "0108",
			"nombre" => "Petapa",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 82,
			"codigo" => "0109",
			"nombre" => "San José del Golfo",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 83,
			"codigo" => "0110",
			"nombre" => "San José Pinula",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 84,
			"codigo" => "0111",
			"nombre" => "San Juan Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 85,
			"codigo" => "0112",
			"nombre" => "San Pedro Ayampuc",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 86,
			"codigo" => "0113",
			"nombre" => "San Pedro Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 87,
			"codigo" => "0114",
			"nombre" => "San Raymundo",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 88,
			"codigo" => "0115",
			"nombre" => "Santa Catarina Pinula",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 89,
			"codigo" => "0116",
			"nombre" => "Villa Canales",
			"activo" => 1,
			"departamento_id" => 8
		],
		[
			"id" => 90,
			"codigo" => 1301,
			"nombre" => "Aguacatán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 91,
			"codigo" => 1302,
			"nombre" => "Chiantla",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 92,
			"codigo" => 1303,
			"nombre" => "Colotenango",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 93,
			"codigo" => 1304,
			"nombre" => "Concepción Huista",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 94,
			"codigo" => 1305,
			"nombre" => "Cuilco",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 95,
			"codigo" => 1306,
			"nombre" => "Huehuetenango",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 96,
			"codigo" => 1307,
			"nombre" => "Ixtahuacán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 97,
			"codigo" => 1308,
			"nombre" => "Jacaltenango",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 98,
			"codigo" => 1309,
			"nombre" => "La Democracia",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 99,
			"codigo" => 1310,
			"nombre" => "La Libertad",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 100,
			"codigo" => 1311,
			"nombre" => "Malacatancito",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 101,
			"codigo" => 1312,
			"nombre" => "Nentón",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 102,
			"codigo" => 1313,
			"nombre" => "San Antonio Huista",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 103,
			"codigo" => 1314,
			"nombre" => "San Gaspar Ixchil",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 104,
			"codigo" => 1315,
			"nombre" => "San Juan Atitán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 105,
			"codigo" => 1316,
			"nombre" => "San Juan Ixcoy",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 106,
			"codigo" => 1317,
			"nombre" => "San Mateo Ixtatán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 107,
			"codigo" => 1318,
			"nombre" => "San Miguel Acatán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 108,
			"codigo" => 1319,
			"nombre" => "San Pedro Necta",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 109,
			"codigo" => 1320,
			"nombre" => "San Rafael La Independencia",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 110,
			"codigo" => 1321,
			"nombre" => "San Rafael Petzal",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 111,
			"codigo" => 1322,
			"nombre" => "San Sebastián Coatán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 112,
			"codigo" => 1323,
			"nombre" => "San Sebastián Huehuetenango",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 113,
			"codigo" => 1324,
			"nombre" => "Santa Ana Huista",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 114,
			"codigo" => 1325,
			"nombre" => "Santa Bárbara",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 115,
			"codigo" => 1326,
			"nombre" => "Santa Cruz Barillas",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 116,
			"codigo" => 1327,
			"nombre" => "Santa Eulalia",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 117,
			"codigo" => 1328,
			"nombre" => "Santiago Chimaltenango",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 118,
			"codigo" => 1329,
			"nombre" => "Soloma",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 119,
			"codigo" => 1330,
			"nombre" => "Tectitán",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 120,
			"codigo" => 1331,
			"nombre" => "Todos Santos Cuchumatan",
			"activo" => 1,
			"departamento_id" => 9
		],
		[
			"id" => 121,
			"codigo" => 1801,
			"nombre" => "El Estor",
			"activo" => 1,
			"departamento_id" => 10
		],
		[
			"id" => 122,
			"codigo" => 1802,
			"nombre" => "Livingston",
			"activo" => 1,
			"departamento_id" => 10
		],
		[
			"id" => 123,
			"codigo" => 1803,
			"nombre" => "Los Amates",
			"activo" => 1,
			"departamento_id" => 10
		],
		[
			"id" => 124,
			"codigo" => 1804,
			"nombre" => "Morales",
			"activo" => 1,
			"departamento_id" => 10
		],
		[
			"id" => 125,
			"codigo" => 1805,
			"nombre" => "Puerto Barrios",
			"activo" => 1,
			"departamento_id" => 10
		],
		[
			"id" => 126,
			"codigo" => 2101,
			"nombre" => "Jalapa",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 127,
			"codigo" => 2102,
			"nombre" => "Mataquescuintla",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 128,
			"codigo" => 2103,
			"nombre" => "Monjas",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 129,
			"codigo" => 2104,
			"nombre" => "San Carlos Alzatate",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 130,
			"codigo" => 2105,
			"nombre" => "San Luis Jilotepeque",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 131,
			"codigo" => 2106,
			"nombre" => "San Manuel Chaparrón",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 132,
			"codigo" => 2107,
			"nombre" => "San Pedro Pinula",
			"activo" => 1,
			"departamento_id" => 11
		],
		[
			"id" => 133,
			"codigo" => 2201,
			"nombre" => "Agua Blanca",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 134,
			"codigo" => 2202,
			"nombre" => "Asunción Mita",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 135,
			"codigo" => 2203,
			"nombre" => "Atescatempa",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 136,
			"codigo" => 2204,
			"nombre" => "Comapa",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 137,
			"codigo" => 2205,
			"nombre" => "Conguaco",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 138,
			"codigo" => 2206,
			"nombre" => "El Adelanto",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 139,
			"codigo" => 2207,
			"nombre" => "El Progreso",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 140,
			"codigo" => 2208,
			"nombre" => "Jalpatagua",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 141,
			"codigo" => 2209,
			"nombre" => "Jerez",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 142,
			"codigo" => 2210,
			"nombre" => "Jutiapa",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 143,
			"codigo" => 2211,
			"nombre" => "Moyuta",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 144,
			"codigo" => 2212,
			"nombre" => "Pasaco",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 145,
			"codigo" => 2213,
			"nombre" => "Quezada",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 146,
			"codigo" => 2214,
			"nombre" => "San José Acatempa",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 147,
			"codigo" => 2215,
			"nombre" => "Santa Catarina Mita",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 148,
			"codigo" => 2216,
			"nombre" => "Yupiltepeque",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 149,
			"codigo" => 2217,
			"nombre" => "Zapotitlán",
			"activo" => 1,
			"departamento_id" => 12
		],
		[
			"id" => 150,
			"codigo" => 1701,
			"nombre" => "Dolores",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 151,
			"codigo" => 1702,
			"nombre" => "Flores",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 152,
			"codigo" => 1703,
			"nombre" => "La Libertad",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 153,
			"codigo" => 1704,
			"nombre" => "Melchor de Mencos",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 154,
			"codigo" => 1705,
			"nombre" => "Poptún",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 155,
			"codigo" => 1706,
			"nombre" => "San Andrés",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 156,
			"codigo" => 1707,
			"nombre" => "San Benito",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 157,
			"codigo" => 1708,
			"nombre" => "San Francisco",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 158,
			"codigo" => 1709,
			"nombre" => "San José",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 159,
			"codigo" => 1710,
			"nombre" => "San Luis",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 160,
			"codigo" => 1711,
			"nombre" => "Santa Ana",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 161,
			"codigo" => 1712,
			"nombre" => "Sayaxché",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 162,
			"codigo" => 1713,
			"nombre" => "Las Cruces",
			"activo" => 1,
			"departamento_id" => 13
		],
		[
			"id" => 163,
			"codigo" => "0901",
			"nombre" => "Almolonga",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 164,
			"codigo" => "0902",
			"nombre" => "Cabricán",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 165,
			"codigo" => "0903",
			"nombre" => "Cajolá",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 166,
			"codigo" => "0904",
			"nombre" => "Cantel",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 167,
			"codigo" => "0905",
			"nombre" => "Coatepeque",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 168,
			"codigo" => "0906",
			"nombre" => "Colomba",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 169,
			"codigo" => "0907",
			"nombre" => "Concepción Chiquirichapa",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 170,
			"codigo" => "0908",
			"nombre" => "El Palmar",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 171,
			"codigo" => "0909",
			"nombre" => "Flores Costa Cuca",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 172,
			"codigo" => "0910",
			"nombre" => "Génova",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 173,
			"codigo" => "0911",
			"nombre" => "Huitán",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 174,
			"codigo" => "0912",
			"nombre" => "La Esperanza",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 175,
			"codigo" => "0913",
			"nombre" => "Olintepeque",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 176,
			"codigo" => "0914",
			"nombre" => "Ostuncalco",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 177,
			"codigo" => "0915",
			"nombre" => "Palestina de Los Altos",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 178,
			"codigo" => "0916",
			"nombre" => "Quetzaltenango",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 179,
			"codigo" => "0917",
			"nombre" => "Salcajá",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 180,
			"codigo" => "0918",
			"nombre" => "San Carlos Sija",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 181,
			"codigo" => "0919",
			"nombre" => "San Francisco La Unión",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 182,
			"codigo" => "0920",
			"nombre" => "San Martín Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 183,
			"codigo" => "0921",
			"nombre" => "San Mateo",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 184,
			"codigo" => "0922",
			"nombre" => "San Miguel Sigüilá",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 185,
			"codigo" => "0923",
			"nombre" => "Sibilia",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 186,
			"codigo" => "0924",
			"nombre" => "Zunil",
			"activo" => 1,
			"departamento_id" => 14
		],
		[
			"id" => 187,
			"codigo" => 1401,
			"nombre" => "Canillá",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 188,
			"codigo" => 1402,
			"nombre" => "Chajul",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 189,
			"codigo" => 1403,
			"nombre" => "Chicamán",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 190,
			"codigo" => 1404,
			"nombre" => "Chiché",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 191,
			"codigo" => 1405,
			"nombre" => "Chichicastenango",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 192,
			"codigo" => 1406,
			"nombre" => "Chinique",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 193,
			"codigo" => 1407,
			"nombre" => "Cunén",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 194,
			"codigo" => 1408,
			"nombre" => "Ixcán",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 195,
			"codigo" => 1409,
			"nombre" => "Joyabaj",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 196,
			"codigo" => 1410,
			"nombre" => "Nebaj",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 197,
			"codigo" => 1411,
			"nombre" => "Pachalum",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 198,
			"codigo" => 1412,
			"nombre" => "Patzité",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 199,
			"codigo" => 1413,
			"nombre" => "Sacapulas",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 200,
			"codigo" => 1414,
			"nombre" => "San Andrés Sajcabajá",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 201,
			"codigo" => 1415,
			"nombre" => "San Antonio Ilotenango",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 202,
			"codigo" => 1416,
			"nombre" => "San Bartolomé Jocotenango",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 203,
			"codigo" => 1417,
			"nombre" => "San Juan Cotzal",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 204,
			"codigo" => 1418,
			"nombre" => "San Pedro Jocopilas",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 205,
			"codigo" => 1419,
			"nombre" => "Santa Cruz del Quiché",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 206,
			"codigo" => 1420,
			"nombre" => "Uspantán",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 207,
			"codigo" => 1421,
			"nombre" => "Zacualpa",
			"activo" => 1,
			"departamento_id" => 15
		],
		[
			"id" => 208,
			"codigo" => 1101,
			"nombre" => "Champerico",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 209,
			"codigo" => 1102,
			"nombre" => "El Asintal",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 210,
			"codigo" => 1103,
			"nombre" => "Nuevo San Carlos",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 211,
			"codigo" => 1104,
			"nombre" => "Retalhuleu",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 212,
			"codigo" => 1105,
			"nombre" => "San Andrés Villa Seca",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 213,
			"codigo" => 1106,
			"nombre" => "San Felipe",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 214,
			"codigo" => 1107,
			"nombre" => "San Martín Zapotitlán",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 215,
			"codigo" => 1108,
			"nombre" => "San Sebastián",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 216,
			"codigo" => 1109,
			"nombre" => "Santa Cruz Muluá",
			"activo" => 1,
			"departamento_id" => 16
		],
		[
			"id" => 217,
			"codigo" => "0301",
			"nombre" => "Alotenango",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 218,
			"codigo" => "0302",
			"nombre" => "Antigua",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 219,
			"codigo" => "0303",
			"nombre" => "Ciudad Vieja",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 220,
			"codigo" => "0304",
			"nombre" => "Jocotenango",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 221,
			"codigo" => "0305",
			"nombre" => "Magdalena Milpas Altas",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 222,
			"codigo" => "0306",
			"nombre" => "Pastores",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 223,
			"codigo" => "0307",
			"nombre" => "San Antonio Aguas Calientes",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 224,
			"codigo" => "0308",
			"nombre" => "San Bartolomé Milpas Altas",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 225,
			"codigo" => "0309",
			"nombre" => "San Lucas Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 226,
			"codigo" => "0310",
			"nombre" => "San Miguel Dueñas",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 227,
			"codigo" => "0311",
			"nombre" => "Santa Catarina Barahona",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 228,
			"codigo" => "0312",
			"nombre" => "Santa Lucía Milpas Altas",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 229,
			"codigo" => "0313",
			"nombre" => "Santa María de Jesús",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 230,
			"codigo" => "0314",
			"nombre" => "Santiago Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 231,
			"codigo" => "0315",
			"nombre" => "Santo Domingo Xenacoj",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 232,
			"codigo" => "0316",
			"nombre" => "Sumpango",
			"activo" => 1,
			"departamento_id" => 17
		],
		[
			"id" => 233,
			"codigo" => 1201,
			"nombre" => "Ayutla",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 234,
			"codigo" => 1202,
			"nombre" => "Catarina",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 235,
			"codigo" => 1203,
			"nombre" => "Comitancillo",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 236,
			"codigo" => 1204,
			"nombre" => "Concepción Tutuapa",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 237,
			"codigo" => 1205,
			"nombre" => "El Quetzal",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 238,
			"codigo" => 1206,
			"nombre" => "El Rodeo",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 239,
			"codigo" => 1207,
			"nombre" => "El Tumbador",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 240,
			"codigo" => 1208,
			"nombre" => "Esquipulas Palo Gordo",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 241,
			"codigo" => 1209,
			"nombre" => "Ixchiguan",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 242,
			"codigo" => 1210,
			"nombre" => "La Reforma",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 243,
			"codigo" => 1211,
			"nombre" => "Malacatán",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 244,
			"codigo" => 1212,
			"nombre" => "Nuevo Progreso",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 245,
			"codigo" => 1213,
			"nombre" => "Ocos",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 246,
			"codigo" => 1214,
			"nombre" => "Pajapita",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 247,
			"codigo" => 1215,
			"nombre" => "Río Blanco",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 248,
			"codigo" => 1216,
			"nombre" => "San Antonio Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 249,
			"codigo" => 1217,
			"nombre" => "San Cristóbal Cucho",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 250,
			"codigo" => 1218,
			"nombre" => "San José Ojetenam",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 251,
			"codigo" => 1219,
			"nombre" => "San Lorenzo",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 252,
			"codigo" => 1220,
			"nombre" => "San Marcos",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 253,
			"codigo" => 1221,
			"nombre" => "San Miguel Ixtahuacán",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 254,
			"codigo" => 1222,
			"nombre" => "San Pablo",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 255,
			"codigo" => 1223,
			"nombre" => "San Pedro Sacatepéquez",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 256,
			"codigo" => 1224,
			"nombre" => "San Rafael Pie de La Cuesta",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 257,
			"codigo" => 1225,
			"nombre" => "San Sibinal",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 258,
			"codigo" => 1226,
			"nombre" => "Sipacapa",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 259,
			"codigo" => 1227,
			"nombre" => "Tacaná",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 260,
			"codigo" => 1228,
			"nombre" => "Tajumulco",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 261,
			"codigo" => 1229,
			"nombre" => "Tejutla",
			"activo" => 1,
			"departamento_id" => 18
		],
		[
			"id" => 262,
			"codigo" => "0601",
			"nombre" => "Barberena",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 263,
			"codigo" => "0602",
			"nombre" => "Casillas",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 264,
			"codigo" => "0603",
			"nombre" => "Chiquimulilla",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 265,
			"codigo" => "0604",
			"nombre" => "Cuilapa",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 266,
			"codigo" => "0605",
			"nombre" => "Guazacapán",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 267,
			"codigo" => "0606",
			"nombre" => "Nueva Santa Rosa",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 268,
			"codigo" => "0607",
			"nombre" => "Oratorio",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 269,
			"codigo" => "0608",
			"nombre" => "Pueblo Nuevo Viñas",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 270,
			"codigo" => "0609",
			"nombre" => "San Juan Tecuaco",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 271,
			"codigo" => "0610",
			"nombre" => "San Rafael Las Flores",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 272,
			"codigo" => "0611",
			"nombre" => "Santa Cruz Naranjo",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 273,
			"codigo" => "0612",
			"nombre" => "Santa María Ixhuatán",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 274,
			"codigo" => "0613",
			"nombre" => "Santa Rosa de Lima",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 275,
			"codigo" => "0614",
			"nombre" => "Taxisco",
			"activo" => 1,
			"departamento_id" => 19
		],
		[
			"id" => 276,
			"codigo" => "0701",
			"nombre" => "Concepción",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 277,
			"codigo" => "0702",
			"nombre" => "Nahualá",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 278,
			"codigo" => "0703",
			"nombre" => "Panajachel",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 279,
			"codigo" => "0704",
			"nombre" => "San Andrés Semetabaj",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 280,
			"codigo" => "0705",
			"nombre" => "San Antonio Palopó",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 281,
			"codigo" => "0706",
			"nombre" => "San José Chacaya",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 282,
			"codigo" => "0707",
			"nombre" => "San Juan La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 283,
			"codigo" => "0708",
			"nombre" => "San Lucas Tolimán",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 284,
			"codigo" => "0709",
			"nombre" => "San Marcos La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 285,
			"codigo" => "0710",
			"nombre" => "San Pablo La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 286,
			"codigo" => "0711",
			"nombre" => "San Pedro La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 287,
			"codigo" => "0712",
			"nombre" => "Santa Catarina Ixtahuacan",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 288,
			"codigo" => "0713",
			"nombre" => "Santa Catarina Palopó",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 289,
			"codigo" => "0714",
			"nombre" => "Santa Clara La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 290,
			"codigo" => "0715",
			"nombre" => "Santa Cruz La Laguna",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 291,
			"codigo" => "0716",
			"nombre" => "Santa Lucía Utatlán",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 292,
			"codigo" => "0717",
			"nombre" => "Santa María Visitación",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 293,
			"codigo" => "0718",
			"nombre" => "Santiago Atitlán",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 294,
			"codigo" => "0719",
			"nombre" => "Sololá",
			"activo" => 1,
			"departamento_id" => 20
		],
		[
			"id" => 295,
			"codigo" => 1001,
			"nombre" => "Chicacao",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 296,
			"codigo" => 1002,
			"nombre" => "Cuyotenango",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 297,
			"codigo" => 1003,
			"nombre" => "Mazatenango",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 298,
			"codigo" => 1004,
			"nombre" => "Patulul",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 299,
			"codigo" => 1005,
			"nombre" => "Pueblo Nuevo",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 300,
			"codigo" => 1006,
			"nombre" => "Río Bravo",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 301,
			"codigo" => 1007,
			"nombre" => "Samayac",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 302,
			"codigo" => 1008,
			"nombre" => "San Antonio Suchitepéquez",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 303,
			"codigo" => 1009,
			"nombre" => "San Bernardino",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 304,
			"codigo" => 1010,
			"nombre" => "San Francisco Zapotitlán",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 305,
			"codigo" => 1011,
			"nombre" => "San Gabriel",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 306,
			"codigo" => 1012,
			"nombre" => "San José El Idolo",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 307,
			"codigo" => 1013,
			"nombre" => "San Juan Bautista",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 308,
			"codigo" => 1014,
			"nombre" => "San Lorenzo",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 309,
			"codigo" => 1015,
			"nombre" => "San Miguel Panán",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 310,
			"codigo" => 1016,
			"nombre" => "San Pablo Jocopilas",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 311,
			"codigo" => 1017,
			"nombre" => "Santa Bárbara",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 312,
			"codigo" => 1018,
			"nombre" => "Santo Domingo Suchitepequez",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 313,
			"codigo" => 1019,
			"nombre" => "Santo Tomas La Unión",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 314,
			"codigo" => 1020,
			"nombre" => "Zunilito",
			"activo" => 1,
			"departamento_id" => 21
		],
		[
			"id" => 315,
			"codigo" => "0801",
			"nombre" => "Momostenango",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 316,
			"codigo" => "0802",
			"nombre" => "San Andrés Xecul",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 317,
			"codigo" => "0803",
			"nombre" => "San Bartolo",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 318,
			"codigo" => "0804",
			"nombre" => "San Cristóbal Totonicapán",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 319,
			"codigo" => "0805",
			"nombre" => "San Francisco El Alto",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 320,
			"codigo" => "0806",
			"nombre" => "Santa Lucía La Reforma",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 321,
			"codigo" => "0807",
			"nombre" => "Santa María Chiquimula",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 322,
			"codigo" => "0808",
			"nombre" => "Totonicapán",
			"activo" => 1,
			"departamento_id" => 22
		],
		[
			"id" => 323,
			"codigo" => 1901,
			"nombre" => "Cabañas",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 324,
			"codigo" => 1902,
			"nombre" => "Estanzuela",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 325,
			"codigo" => 1903,
			"nombre" => "Gualán",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 326,
			"codigo" => 1904,
			"nombre" => "Huité",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 327,
			"codigo" => 1905,
			"nombre" => "La Unión",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 328,
			"codigo" => 1906,
			"nombre" => "Río Hondo",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 329,
			"codigo" => 1907,
			"nombre" => "San Diego",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 330,
			"codigo" => 1908,
			"nombre" => "Teculután",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 331,
			"codigo" => 1909,
			"nombre" => "Usumatlán",
			"activo" => 1,
			"departamento_id" => 23
		],
		[
			"id" => 332,
			"codigo" => 1910,
			"nombre" => "Zacapa",
			"activo" => 1,
			"departamento_id" => 23
		]
	],

	# Menú del sistema (global)
	"modulo" => [
		[
			"id" => 1,
			"nombre" => "Catálogos",
			"icono" => "fa fa-list",
			"url" => "/",
			"orden" => 1,
			"detalle" => 1,
			"activo" => 1
		],
		[
			"id" => 2,
			"nombre" => "Compra",
			"icono" => "fa fa-cart-shopping",
			"url" => "/",
			"orden" => 2,
			"detalle" => 1,
			"activo" => 1
		],
		[
			"id" => 3,
			"nombre" => "Venta",
			"icono" => "fa fa-cash-register",
			"url" => "/venta",
			"orden" => 3,
			"detalle" => 0,
			"activo" => 1
		],
		[
			"id" => 4,
			"nombre" => "Cotización",
			"icono" => "fa fa-file-invoice-dollar",
			"url" => "/cotizacion",
			"orden" => 4,
			"detalle" => 0,
			"activo" => 1
		],
		[
			"id" => 5,
			"nombre" => "Configuración",
			"icono" => "fa fa-cogs",
			"url" => "/",
			"orden" => 5,
			"detalle" => 1,
			"activo" => 1
		],
		[
			"id" => 8,
			"nombre" => "Inventario",
			"icono" => "fa-solid fa-layer-group",
			"url" => null,
			"orden" => 5,
			"detalle" => 1,
			"activo" => 1
		],
		[
			"id" => 9,
			"nombre" => "Finanzas",
			"icono" => "fa-solid fa-wallet",
			"url" => null,
			"orden" => 6,
			"detalle" => 1,
			"activo" => 1
		],
		[
			"id" => 10,
			"nombre" => "Reportes",
			"icono" => "fa-solid fa-chart-column",
			"url" => null,
			"orden" => 7,
			"detalle" => 1,
			"activo" => 1
		]
	],

	# Opciones de cada módulo (la url es la ruta de la interfaz)
	"menu" => [
		[
			"id" => 1,
			"modulo_id" => 1,
			"nombre" => "Moneda",
			"orden" => 1,
			"icono" => "fas fa-coins",
			"url" => "/moneda",
			"activo" => 1
		],
		[
			"id" => 2,
			"modulo_id" => 1,
			"nombre" => "Unidad de medida",
			"orden" => 2,
			"icono" => "fas fa-scale-balanced",
			"url" => "/unidad_medida",
			"activo" => 1
		],
		[
			"id" => 3,
			"modulo_id" => 1,
			"nombre" => "Categorías",
			"orden" => 3,
			"icono" => "fas fa-folder-tree",
			"url" => "/categoria",
			"activo" => 1
		],
		[
			"id" => 4,
			"modulo_id" => 1,
			"nombre" => "Marca",
			"orden" => 4,
			"icono" => "fas fa-tag",
			"url" => "/marca",
			"activo" => 1
		],
		[
			"id" => 5,
			"modulo_id" => 5,
			"nombre" => "Parámetros",
			"orden" => 1,
			"icono" => "fas fa-sliders-h",
			"url" => "/parametros",
			"activo" => 1
		],
		[
			"id" => 6,
			"modulo_id" => 5,
			"nombre" => "Sucursal",
			"orden" => 3,
			"icono" => "fas fa-home",
			"url" => "/sucursal",
			"activo" => 1
		],
		[
			"id" => 7,
			"modulo_id" => 5,
			"nombre" => "Usuario",
			"orden" => 2,
			"icono" => "fas fa-user",
			"url" => "/usuario",
			"activo" => 1
		],
		[
			"id" => 8,
			"modulo_id" => 5,
			"nombre" => "Rol",
			"orden" => 4,
			"icono" => "fas fa-user-shield",
			"url" => "/rol",
			"activo" => 1
		],
		[
			"id" => 9,
			"modulo_id" => 2,
			"nombre" => "Proveedor",
			"orden" => 2,
			"icono" => "fas fa-truck",
			"url" => "/proveedor",
			"activo" => 1
		],
		[
			"id" => 10,
			"modulo_id" => 2,
			"nombre" => "Orden de compra",
			"orden" => 1,
			"icono" => "fas fa-file-invoice",
			"url" => "/compra",
			"activo" => 1
		],
		[
			"id" => 11,
			"modulo_id" => 5,
			"nombre" => "Menú",
			"orden" => 5,
			"icono" => "fa fa-list-alt",
			"url" => "/menu",
			"activo" => 1
		],
		[
			"id" => 12,
			"modulo_id" => 8,
			"nombre" => "Existencias",
			"orden" => 0,
			"icono" => null,
			"url" => "/existencia",
			"activo" => 1
		],
		[
			"id" => 13,
			"modulo_id" => 8,
			"nombre" => "Kardex",
			"orden" => 2,
			"icono" => "fa-regular fa-circle",
			"url" => "/kardex",
			"activo" => 1
		],
		[
			"id" => 14,
			"modulo_id" => 8,
			"nombre" => "Ajustes",
			"orden" => 3,
			"icono" => "fa-regular fa-circle",
			"url" => "/ajuste",
			"activo" => 1
		],
		[
			"id" => 15,
			"modulo_id" => 1,
			"nombre" => "Producto",
			"orden" => 5,
			"icono" => "fa-regular fa-circle",
			"url" => "/producto",
			"activo" => 1
		],
		[
			"id" => 16,
			"modulo_id" => 8,
			"nombre" => "Inventario Inicial",
			"orden" => 4,
			"icono" => "fa-regular fa-circle",
			"url" => "/inventario",
			"activo" => 1
		],
		[
			"id" => 17,
			"modulo_id" => 1,
			"nombre" => "Clientes",
			"orden" => 6,
			"icono" => "fa fa-users",
			"url" => "/cliente",
			"activo" => 1
		],
		[
			"id" => 18,
			"modulo_id" => 9,
			"nombre" => "Cuentas por cobrar",
			"orden" => 1,
			"icono" => "fa-solid fa-hand-holding-dollar",
			"url" => "/cuenta-cobrar",
			"activo" => 1
		],
		[
			"id" => 19,
			"modulo_id" => 9,
			"nombre" => "Cuentas por pagar",
			"orden" => 1,
			"icono" => "fa-solid fa-money-bill-transfer",
			"url" => "/cuenta-pagar",
			"activo" => 1
		],
		[
			"id" => 20,
			"modulo_id" => 8,
			"nombre" => "Conversiones",
			"orden" => 5,
			"icono" => "fa-regular fa-circle",
			"url" => "/conversion",
			"activo" => 1
		],
		[
			"id" => 21,
			"modulo_id" => 10,
			"nombre" => "Ventas por día",
			"orden" => 1,
			"icono" => "fa-regular fa-circle",
			"url" => "/ventas-dia",
			"activo" => 1
		],
		[
			"id" => 22,
			"modulo_id" => 1,
			"nombre" => "Listas de precios",
			"orden" => 7,
			"icono" => "fa-solid fa-tags",
			"url" => "/lista-precio",
			"activo" => 1
		],
		[
			"id" => 23,
			"modulo_id" => 8,
			"nombre" => "Traslados",
			"orden" => 6,
			"icono" => "fa-regular fa-circle",
			"url" => "/traslado",
			"activo" => 1
		]
	],

	# Estados de venta (global; ids usados en Venta_model)
	"venta_estado" => [
		[
			"id" => 1,
			"nombre" => "Creado",
			"orden" => 1,
			"activo" => 1,
			"etiqueta" => "badge bg-warning"
		],
		[
			"id" => 2,
			"nombre" => "Facturada",
			"orden" => 2,
			"activo" => 1,
			"etiqueta" => "badge bg-green"
		],
		[
			"id" => 3,
			"nombre" => "Pagada",
			"orden" => 3,
			"activo" => 1,
			"etiqueta" => "badge bg-primary"
		],
		[
			"id" => 4,
			"nombre" => "Anulada",
			"orden" => 4,
			"activo" => 1,
			"etiqueta" => "badge bg-danger"
		]
	],

	# Por empresa: tipos de movimiento (el código se usa en compras, ventas y ajustes)
	"movimiento_tipo" => [
		[
			"id" => 1,
			"codigo" => "REC",
			"nombre" => "RECEPCIÓN",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "AJP",
			"nombre" => "Ajuste de entrada",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 3,
			"codigo" => "AJN",
			"nombre" => "Ajuste de salida",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 4,
			"codigo" => "IVP",
			"nombre" => "Inventario positivo",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 5,
			"codigo" => "IVN",
			"nombre" => "Inventario negativo",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 6,
			"codigo" => "VTA",
			"nombre" => "Venta",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 7,
			"codigo" => "VAN",
			"nombre" => "Anulacion de venta",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 8,
			"codigo" => "AAE",
			"nombre" => "Anulación de ajuste de entrada",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 9,
			"codigo" => "AAS",
			"nombre" => "Anulación de ajuste de salida",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 10,
			"codigo" => "AIP",
			"nombre" => "Anulación de inventario positivo",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 11,
			"codigo" => "AIN",
			"nombre" => "Anulación de inventario negativo",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 12,
			"codigo" => "CVS",
			"nombre" => "Conversión salida",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 13,
			"codigo" => "CVE",
			"nombre" => "Conversión entrada",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 14,
			"codigo" => "TRS",
			"nombre" => "Traslado salida",
			"sentido" => "SALIDA",
			"activo" => 1
		],
		[
			"id" => 15,
			"codigo" => "TRE",
			"nombre" => "Traslado entrada",
			"sentido" => "ENTRADA",
			"activo" => 1
		],
		[
			"id" => 16,
			"codigo" => "ATS",
			"nombre" => "Anulación de traslado salida",
			"sentido" => "ENTRADA",
			"activo" => 1
		]
	],

	# Por empresa: estados de compra (ids usados en Compra_model)
	"compra_estado" => [
		[
			"id" => 1,
			"nombre" => "Creada",
			"activo" => 1,
			"etiqueta" => "primary"
		],
		[
			"id" => 2,
			"nombre" => "Recibida",
			"activo" => 1,
			"etiqueta" => "lime"
		],
		[
			"id" => 3,
			"nombre" => "Anulada",
			"activo" => 1,
			"etiqueta" => "danger"
		]
	],

	# Por empresa: estados de cotización
	"cotizacion_estado" => [
		[
			"id" => 1,
			"codigo" => "BORRADOR",
			"nombre" => "Borrador",
			"orden" => 10,
			"activo" => 1,
			"etiqueta" => "badge bg-warning"
		],
		[
			"id" => 2,
			"codigo" => "ENVIADA",
			"nombre" => "Enviada",
			"orden" => 20,
			"activo" => 1,
			"etiqueta" => "badge bg-info"
		],
		[
			"id" => 3,
			"codigo" => "ACEPTADA",
			"nombre" => "Aceptada",
			"orden" => 30,
			"activo" => 1,
			"etiqueta" => "badge bg-success"
		],
		[
			"id" => 4,
			"codigo" => "RECHAZADA",
			"nombre" => "Rechazada",
			"orden" => 40,
			"activo" => 1,
			"etiqueta" => "badge bg-danger"
		],
		[
			"id" => 5,
			"codigo" => "ANULADA",
			"nombre" => "Anulada",
			"orden" => 50,
			"activo" => 1,
			"etiqueta" => "badge bg-secondary"
		],
		[
			"id" => 6,
			"codigo" => "CONVERTIDA",
			"nombre" => "Convertida",
			"orden" => 60,
			"activo" => 1,
			"etiqueta" => "badge bg-primary"
		]
	],

	# Por empresa: estados de ajuste (ids usados en Inventario_ajuste_model)
	"inventario_ajuste_estado" => [
		[
			"id" => 1,
			"codigo" => "BORRADOR",
			"nombre" => "Borrador",
			"etiqueta" => "primary",
			"orden" => 10,
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "APLICADO",
			"nombre" => "Aplicado",
			"etiqueta" => "lime",
			"orden" => 20,
			"activo" => 1
		],
		[
			"id" => 3,
			"codigo" => "ANULADO",
			"nombre" => "Anulado",
			"etiqueta" => "danger",
			"orden" => 30,
			"activo" => 1
		]
	],

	# Por empresa: estados de traslado (ids usados en Inventario_traslado_model)
	"inventario_traslado_estado" => [
		[
			"id" => 1,
			"codigo" => "BORRADOR",
			"nombre" => "Borrador",
			"etiqueta" => "primary",
			"orden" => 10,
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "ENVIADO",
			"nombre" => "Enviado",
			"etiqueta" => "warning",
			"orden" => 20,
			"activo" => 1
		],
		[
			"id" => 3,
			"codigo" => "RECIBIDO",
			"nombre" => "Recibido",
			"etiqueta" => "lime",
			"orden" => 30,
			"activo" => 1
		],
		[
			"id" => 4,
			"codigo" => "ANULADO",
			"nombre" => "Anulado",
			"etiqueta" => "danger",
			"orden" => 40,
			"activo" => 1
		]
	],

	# Por empresa: motivos de ajuste
	"inventario_ajuste_tipo" => [
		[
			"id" => 1,
			"codigo" => "INI",
			"nombre" => "Inventario inicial",
			"movimiento_tipo_id" => 2,
			"requiere_observacion" => 0,
			"activo" => 0
		],
		[
			"id" => 2,
			"codigo" => "SOB",
			"nombre" => "Sobrante en conteo",
			"movimiento_tipo_id" => 2,
			"requiere_observacion" => 1,
			"activo" => 0
		],
		[
			"id" => 3,
			"codigo" => "RCP",
			"nombre" => "Recuperacion de producto",
			"movimiento_tipo_id" => 2,
			"requiere_observacion" => 1,
			"activo" => 1
		],
		[
			"id" => 4,
			"codigo" => "FAL",
			"nombre" => "Faltante en conteo",
			"movimiento_tipo_id" => 3,
			"requiere_observacion" => 1,
			"activo" => 0
		],
		[
			"id" => 5,
			"codigo" => "MER",
			"nombre" => "Merma",
			"movimiento_tipo_id" => 3,
			"requiere_observacion" => 1,
			"activo" => 1
		],
		[
			"id" => 6,
			"codigo" => "DAN",
			"nombre" => "Producto danado",
			"movimiento_tipo_id" => 3,
			"requiere_observacion" => 1,
			"activo" => 1
		],
		[
			"id" => 7,
			"codigo" => "VEN",
			"nombre" => "Producto vencido",
			"movimiento_tipo_id" => 3,
			"requiere_observacion" => 1,
			"activo" => 1
		],
		[
			"id" => 8,
			"codigo" => "CON",
			"nombre" => "Consumo interno",
			"movimiento_tipo_id" => 3,
			"requiere_observacion" => 1,
			"activo" => 1
		]
	],

	# Por empresa: estados de inventario
	"inventario_estado" => [
		[
			"id" => 1,
			"codigo" => "BORRADOR",
			"nombre" => "Borrador",
			"orden" => 10,
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "VALIDADO",
			"nombre" => "Validado",
			"orden" => 20,
			"activo" => 0
		],
		[
			"id" => 3,
			"codigo" => "PROCESADO",
			"nombre" => "Procesado",
			"orden" => 30,
			"activo" => 1
		],
		[
			"id" => 4,
			"codigo" => "ANULADO",
			"nombre" => "Anulado",
			"orden" => 40,
			"activo" => 1
		]
	],

	# Por empresa: tipos de inventario
	"inventario_tipo" => [
		[
			"id" => 1,
			"codigo" => "INICIAL",
			"nombre" => "Inventario inicial",
			"descripcion" => "Carga de las existencias iniciales de una sucursal.",
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "CICLICO",
			"nombre" => "Inventario ciclico",
			"descripcion" => "Conteo fisico parcial o periodico para determinar diferencias.",
			"activo" => 1
		]
	],

	# Por empresa: formas de pago
	"forma_pago" => [
		[
			"id" => 1,
			"nombre" => "Contado",
			"activo" => 0
		],
		[
			"id" => 2,
			"nombre" => "Crédito",
			"activo" => 1
		],
		[
			"id" => 3,
			"nombre" => "Efectivo",
			"activo" => 1
		],
		[
			"id" => 4,
			"nombre" => "Transferencia",
			"activo" => 1
		],
		[
			"id" => 5,
			"nombre" => "Cheque",
			"activo" => 1
		]
	],

	# Por empresa: series de venta (el correlativo arranca en 0)
	"venta_serie" => [
		[
			"id" => 1,
			"nombre" => "Factura interna",
			"codigo" => "FAC",
			"inicio" => 1,
			"fin" => 999999999,
			"correlativo" => 0,
			"electronico" => 0,
			"activo" => 1
		],
		[
			"id" => 2,
			"nombre" => "Recibo interno",
			"codigo" => "REC",
			"inicio" => 1,
			"fin" => 999999999,
			"correlativo" => 0,
			"electronico" => 0,
			"activo" => 1
		]
	],

	# Por empresa: serie de cotizaciones (sin una serie activa no se pueden crear cotizaciones)
	"cotizacion_serie" => [
		[
			"id" => 1,
			"nombre" => "Serie de cotizaciones",
			"codigo" => "COT",
			"inicio" => 1,
			"fin" => 999999999,
			"correlativo" => 0,
			"activo" => 1
		]
	],

	# Por empresa: unidades de medida comunes (el usuario agrega las demás)
	"unidad_medida" => [
		[
			"id" => 1,
			"codigo" => "UND",
			"nombre" => "Unidad",
			"activo" => 1
		],
		[
			"id" => 2,
			"codigo" => "DOC",
			"nombre" => "Docena",
			"activo" => 1
		],
		[
			"id" => 3,
			"codigo" => "LB",
			"nombre" => "Libra",
			"activo" => 1
		],
		[
			"id" => 4,
			"codigo" => "OZ",
			"nombre" => "Onza",
			"activo" => 1
		],
		[
			"id" => 5,
			"codigo" => "ARR",
			"nombre" => "Arroba",
			"activo" => 1
		],
		[
			"id" => 6,
			"codigo" => "QQ",
			"nombre" => "Quintal",
			"activo" => 1
		],
		[
			"id" => 7,
			"codigo" => "KG",
			"nombre" => "Kilogramo",
			"activo" => 1
		],
		[
			"id" => 8,
			"codigo" => "ML",
			"nombre" => "Mililitro",
			"activo" => 1
		],
		[
			"id" => 9,
			"codigo" => "LT",
			"nombre" => "Litro",
			"activo" => 1
		],
		[
			"id" => 10,
			"codigo" => "GL",
			"nombre" => "Galón",
			"activo" => 1
		],
		[
			"id" => 11,
			"codigo" => "M",
			"nombre" => "Metro",
			"activo" => 1
		]
	],

	# Por empresa: equivalencias de las unidades anteriores (1 unidad_medida_id = cantidad unidad_menor_id)
	"unidad_equivalencia" => [
		[
			"id" => 1,
			"unidad_medida_id" => 6,
			"unidad_menor_id" => 3,
			"cantidad" => 100,
			"activo" => 1
		],
		[
			"id" => 2,
			"unidad_medida_id" => 6,
			"unidad_menor_id" => 5,
			"cantidad" => 4,
			"activo" => 1
		],
		[
			"id" => 3,
			"unidad_medida_id" => 5,
			"unidad_menor_id" => 3,
			"cantidad" => 25,
			"activo" => 1
		],
		[
			"id" => 4,
			"unidad_medida_id" => 3,
			"unidad_menor_id" => 4,
			"cantidad" => 16,
			"activo" => 1
		],
		[
			"id" => 5,
			"unidad_medida_id" => 7,
			"unidad_menor_id" => 3,
			"cantidad" => 2.20462,
			"activo" => 1
		],
		[
			"id" => 6,
			"unidad_medida_id" => 10,
			"unidad_menor_id" => 9,
			"cantidad" => 3.785,
			"activo" => 1
		],
		[
			"id" => 7,
			"unidad_medida_id" => 9,
			"unidad_menor_id" => 8,
			"cantidad" => 1000,
			"activo" => 1
		],
		[
			"id" => 8,
			"unidad_medida_id" => 2,
			"unidad_menor_id" => 1,
			"cantidad" => 12,
			"activo" => 1
		]
	]
];

/* End of file instalacion.php */
/* Location: ./application/config/instalacion.php */
