DELETE FROM recursos WHERE id NOT IN (
    SELECT MIN(id) FROM recursos GROUP BY nombre
);