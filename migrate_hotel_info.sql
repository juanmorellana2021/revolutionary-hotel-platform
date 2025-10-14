-- Hotel Information Migration from XAMPP to VPS
-- This migrates the hotel data while mapping fields between different structures

INSERT INTO hotel_info (
    hotel_name,
    hotel_description, 
    address_line1,
    city,
    state,
    zip_code,
    country,
    phone,
    email,
    website,
    check_in_time,
    check_out_time,
    total_rooms,
    hotel_rating
) VALUES (
    'Samay Wasi Casa De Paz',
    'hola',
    'calle arequipa 286',
    'Pisac, Cusco, Peru', 
    'cusco',
    '08106',
    'Peru',
    '+51938118436',
    'gerente@samaywasihotel.com',
    'https://www.samaywasipisac.com',
    '14:00:00',
    '11:00:00',
    11,
    3.0
);

-- Verify the insertion
SELECT * FROM hotel_info;