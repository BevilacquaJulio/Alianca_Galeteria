USE `alianca_galeteria`;

INSERT INTO `admins` (`name`, `email`, `password_hash`, `role`) VALUES
('Administrador', 'admin@aliancagaleteria.com.br', '$2y$12$FJ00Q120T7z244EhrJwJ5ehaAsf4KGn0yK013hOakbpvlWxF/YvXK', 'admin');

INSERT INTO `categories` (`name`, `slug`, `sort_order`) VALUES
('Galetinhos', 'galetinhos', 1),
('Acompanhamentos', 'acompanhamentos', 2),
('Bebidas', 'bebidas', 3),
('Combos', 'combos', 4),
('Sobremesas', 'sobremesas', 5);

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `featured`) VALUES
(1, 'Galetinho Inteiro Tradicional',   'Frango caipira assado lentamente com ervas aromáticas, alho confitado e tempero artesanal da casa. Porção individual.',    49.90, 1),
(1, 'Galetinho Inteiro Premium',       'Frango caipira premium marinado por 24h em blend de ervas frescas, limão siciliano e páprica defumada. Crocante por fora, suculento por dentro.', 64.90, 1),
(1, 'Meia Galeta Clássica',            'Metade do galetinho tradicional. Ideal para quem prefere uma porção menor sem abrir mão do sabor.', 29.90, 0),
(2, 'Farofa Artesanal da Casa',        'Farofa de mandioca torrada com bacon crocante, cebolinha e ervas frescas. Receita exclusiva.',     18.90, 0),
(2, 'Vinagrete Premium',               'Vinagrete fresco com tomate, pimentão, cebola roxa, coentro e toque de limão. Produzido diariamente.', 12.90, 0),
(2, 'Arroz Temperado Aliança',         'Arroz soltinho refogado com alho dourado, cebola caramelizada e ervas finas.',                     14.90, 0),
(3, 'Suco de Laranja Natural (500ml)', 'Suco de laranja espremido na hora. 100% natural, sem açúcar adicionado.',                          12.00, 0),
(3, 'Refrigerante Lata (350ml)',       'Coca-Cola, Guaraná ou Fanta Laranja. Bem gelado.',                                                   7.50, 0),
(4, 'Combo Família (2 Galetinhos)',    'Dois galetinhos tradicionais inteiros + arroz + farofa + vinagrete. Serve até 4 pessoas.',          109.90, 1),
(5, 'Pudim Artesanal de Leite',        'Pudim caseiro de leite condensado com calda de caramelo. Receita da avó, feito diariamente.',        14.90, 0);

INSERT INTO `stock` (`product_id`, `quantity`, `min_quantity`, `unit`) VALUES
(1, 40, 5, 'un'),
(2, 20, 5, 'un'),
(3, 30, 5, 'un'),
(4, 50, 10, 'porção'),
(5, 60, 10, 'porção'),
(6, 60, 10, 'porção'),
(7, 30, 8, 'un'),
(8, 80, 20, 'un'),
(9, 15, 3, 'un'),
(10, 25, 5, 'un');

INSERT INTO `customers` (`name`, `email`, `phone`, `cpf`, `address`) VALUES
('Maria Aparecida Santos',  'maria.santos@email.com',  '(11) 99887-6543', '123.456.789-00', 'Rua das Flores, 142, Vila Madalena, São Paulo - SP'),
('João Carlos Oliveira',    'joao.oliveira@email.com', '(11) 97654-3210', '987.654.321-00', 'Av. Paulista, 1000, Bela Vista, São Paulo - SP'),
('Ana Paula Rodrigues',     'ana.rodrigues@email.com', '(11) 96543-2109', '456.789.123-00', 'Rua Augusta, 500, Consolação, São Paulo - SP');

INSERT INTO `orders` (`customer_id`, `admin_id`, `status`, `total`) VALUES
(1, 1, 'entregue', 78.80),
(2, 1, 'entregue', 64.90),
(3, 1, 'entregue', 109.90),
(1, 1, 'em_preparo', 49.90),
(2, 1, 'confirmado', 44.80);

INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 49.90, 49.90),
(1, 4, 1, 18.90, 18.90),
(1, 8, 1,  7.50,  7.50),
(2, 2, 1, 64.90, 64.90),
(3, 9, 1, 109.90, 109.90),
(4, 1, 1, 49.90, 49.90),
(5, 3, 1, 29.90, 29.90),
(5, 6, 1, 14.90, 14.90);
