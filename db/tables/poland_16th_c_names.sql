CREATE TABLE public.poland_16th_c_names (
    idx integer NOT NULL,
    fk_ent integer,
    nametype character varying,
    name character varying,
    name_ascii character varying,
    name_lowercase character varying,
    name_ascii_lowercase character varying,
    is_original_string boolean,
    is_full_name boolean,
    contains_separators boolean,
    is_not_name boolean,
    nameval_manually_edited boolean
);
