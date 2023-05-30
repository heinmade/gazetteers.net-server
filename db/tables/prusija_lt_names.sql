CREATE TABLE public.prusija_lt_names (
    idx integer NOT NULL,
    fk_ent integer,
    lang character varying,
    name character varying,
    is_original_string boolean,
    contains_separators boolean,
    is_not_name boolean,
    nameval_manually_edited boolean,
    name_ascii character varying,
    name_lowercase character varying,
    name_ascii_lowercase character varying
);
