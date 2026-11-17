-- ============================================
-- Fix de imágenes rotas en turismo_inkaiko
-- Reemplaza URLs 404 con URLs válidas de Unsplash
-- ============================================

-- URL ROTA: photo-1513051265668  → Ica/Desierto/Paracas
-- Reemplazar con foto de desierto/dunas
UPDATE paquetes SET img = 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=600&q=80' 
WHERE img LIKE '%photo-1513051265668%';

-- URL ROTA: photo-1597160820685  → Lima / ciudad costera
-- Reemplazar con foto de Lima moderna
UPDATE paquetes SET img = 'https://images.unsplash.com/photo-1585464231875-d9ef1f5ad396?w=600&q=80' 
WHERE img LIKE '%photo-1597160820685%';
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1585464231875-d9ef1f5ad396?w=600&q=80' 
WHERE img LIKE '%photo-1597160820685%';

-- URL ROTA: photo-1465408953385  → Cañón del Colca / Arequipa / Cajamarca
-- Reemplazar con foto de cañón/montaña andina
UPDATE paquetes SET img = 'https://images.unsplash.com/photo-1531761535209-180857e963b9?w=600&q=80' 
WHERE img LIKE '%photo-1465408953385%';
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1531761535209-180857e963b9?w=600&q=80' 
WHERE img LIKE '%photo-1465408953385%';

-- URL ROTA: photo-1608430310985  → Chachapoyas / Kuelap
-- Reemplazar con foto de ruinas / arqueología
UPDATE paquetes SET img = 'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=600&q=80' 
WHERE img LIKE '%photo-1608430310985%';
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=600&q=80' 
WHERE img LIKE '%photo-1608430310985%';

-- URL ROTA: photo-1471919743851  → Puno / Fiesta / Lago Titicaca / Amazonía
-- Reemplazar con foto de lago/agua
UPDATE paquetes SET img = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80' 
WHERE img LIKE '%photo-1471919743851%';
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80' 
WHERE img LIKE '%photo-1471919743851%';

-- Verificar URLs de destinos ICA y TRUJILLO (share.google no embebible como img)
-- ICA → foto de Huacachina / desierto
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?w=600&q=80' 
WHERE name = 'ICA' AND img LIKE '%share.google%';

-- TRUJILLO → foto de ciudad colonial
UPDATE destinos SET img = 'https://images.unsplash.com/photo-1580519542036-c47de6196ba5?w=600&q=80' 
WHERE name = 'TRUJILLO' AND img LIKE '%share.google%';

-- Mostrar resultado
SELECT 'Paquetes actualizados:' as info;
SELECT id, name, img FROM paquetes ORDER BY id;
SELECT 'Destinos actualizados:' as info;
SELECT id, name, img FROM destinos ORDER BY id;
