-- UPDATE public."user"
-- 	SET roles = to_jsonb(ARRAY[""])
-- 	WHERE id = 26;

-- UPDATE public."user"

-- UPDATE public."user"
-- 	SET roles = '["ROLE_ADMIN"]'
-- 	WHERE id = 28;


SELECT 
    e.id,
    e.nom_contacte_urgence,
    e.numero_contacte_urgence,
    e.numero_mobile,
    e.sms_send,
    e.classe_id,
    c.label AS classe_label,
    e.promotion,
    e.nationalite,
    e.departement,
    e.communenaissance,
    e.num_secu_social
FROM info_eleve e
LEFT JOIN classe c ON e.classe_id = c.id
ORDER BY e.id;