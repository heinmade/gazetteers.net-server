CREATE TABLE public.naszekaszuby_nameparts (
    idx integer NOT NULL,
    fk_ent integer,
    fk_name integer,
    lang character varying,
    name character varying,
    name_ascii character varying,
    is_original_string boolean,
    is_full_name boolean,
    contains_separators boolean,
    is_not_name boolean,
    namepartval_manually_edited boolean,
    name_lowercase character varying,
    name_ascii_lowercase character varying
);
