DROP TABLE IF EXISTS ma_vie.la_loose;  

CREATE TABLE ma_vie.vertus (  
  la_santé INT CHECK (la_santé BETWEEN 0 AND 100),  
  la_moula INT DEFAULT 0,  
  les_projets TEXT[] NOT NULL,  -- sinon c'est fade
  l_amour BOOLEAN GENERATED ALWAYS AS (TRUE) STORED  
)  
WITH (autovacuum = ON);   

INSERT INTO ma_vie.vertus (la_santé, la_moula, les_projets)  
VALUES  
  (100, 100, ARRAY['écouter le silence', 'danser sous la pluie', 'créer tjr +']),  
  (100, 100, ARRAY['vivre de mes passions', 'apprendre à coder tjrs +']);  

UPDATE ma_vie.quotidien  
SET  
  café = 'il est bon ton café gringo',  
  humeur = 'putain c est beau'  
WHERE  
  réveil < '06:00' AND NOT procrastination;  

CREATE INDEX idx_paix_intérieure  
ON ma_vie.état_d_esprit (méditation, gratitude);  
WHERE méditation IS NOT NULL OR gratitude LIKE '%vin%';   
SAVEPOINT avant_la_chute;  
ROLLBACK TO avant_la_chute; -- Executer ce script : psql -U utopie -d rêves -h localhost
-- pour d'autre script inspirant (music de short #millionaire) 
-- ➡️ https://github.com/berru-g/phylorythme/                     berru-g 12/06/25