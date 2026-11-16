--
-- PostgreSQL database dump
--

\restrict KI4bCGBOZd0T7TBbGPtf4DGMKUTHyhtuWI69ovznEbQSRypULwd7IaBy9xRd99v

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

-- Started on 2026-11-16 19:52:27

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 2 (class 3079 OID 20788)
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- TOC entry 5101 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- TOC entry 281 (class 1255 OID 20827)
-- Name: ajouter_avis(integer, integer, integer, text); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.ajouter_avis(IN p_id_user integer, IN p_id_entreprise integer, IN p_note integer, IN p_commentaire text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_user_exists BOOLEAN;
    v_entreprise_exists BOOLEAN;
    v_avis_exists BOOLEAN;
    v_new_id INTEGER;
    v_entreprise_nom TEXT;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'AJOUT D''UN AVIS';
    RAISE NOTICE 'ID Utilisateur : %', p_id_user;
    RAISE NOTICE 'ID Entreprise : %', p_id_entreprise;
    RAISE NOTICE 'Note : %/5', p_note;
    RAISE NOTICE 'Commentaire : %', LEFT(p_commentaire, 50);

    IF NOT EXISTS (SELECT 1 FROM utilisateurs WHERE id_user = p_id_user) THEN
        RAISE EXCEPTION ' Utilisateur non trouvé avec ID : %', p_id_user;
    END IF;
    
    SELECT EXISTS(SELECT 1 FROM entreprises WHERE id_entreprise = p_id_entreprise) INTO v_entreprise_exists;
    
    IF NOT v_entreprise_exists THEN
        RAISE EXCEPTION ' Entreprise non trouvée avec ID : %', p_id_entreprise;
    END IF;
    SELECT nom INTO v_entreprise_nom FROM entreprises WHERE id_entreprise = p_id_entreprise;
    
    IF p_note IS NULL OR p_note < 1 OR p_note > 5 THEN
        RAISE EXCEPTION ' La note doit être comprise entre 1 et 5 (valeur reçue : %)', p_note;
    END IF;
    IF p_commentaire IS NULL OR TRIM(p_commentaire) = '' THEN
        RAISE EXCEPTION ' Le commentaire ne peut pas être vide';
    END IF;
    SELECT EXISTS(
        SELECT 1 FROM avis 
        WHERE id_user = p_id_user AND id_entreprise = p_id_entreprise
    ) INTO v_avis_exists;
    
    IF v_avis_exists THEN
        RAISE EXCEPTION ' Vous avez déjà donné un avis pour l''entreprise "%"', v_entreprise_nom;
    END IF;
    INSERT INTO avis (id_user, id_entreprise, note, commentaire, date_avis)
    VALUES (p_id_user, p_id_entreprise, p_note, p_commentaire, CURRENT_DATE)
    RETURNING id_avis INTO v_new_id;
    
    RAISE NOTICE '';
    RAISE NOTICE ' AVIS AJOUTÉ AVEC SUCCÈS !';
    RAISE NOTICE '   ID Avis : %', v_new_id;
    RAISE NOTICE '   Entreprise : %', v_entreprise_nom;
    RAISE NOTICE '   Note : %/5', p_note;
    RAISE NOTICE '   Commentaire : %', p_commentaire;
    RAISE NOTICE '   Date : %', CURRENT_DATE;
    RAISE NOTICE ' Merci pour votre avis !';
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '';
        RAISE NOTICE 'ÉCHEC DE L''AJOUT DE L''AVIS';
        RAISE NOTICE '   Erreur : %', SQLERRM;
        RAISE NOTICE '';
        RAISE;
END;
$$;


ALTER PROCEDURE public.ajouter_avis(IN p_id_user integer, IN p_id_entreprise integer, IN p_note integer, IN p_commentaire text) OWNER TO postgres;

--
-- TOC entry 256 (class 1255 OID 20826)
-- Name: inscription_utilisateur(text, text, text, text); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.inscription_utilisateur(IN p_nom text, IN p_email text, IN p_password text, IN p_confirm_password text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_hash TEXT;
    v_user_exists BOOLEAN;
    v_new_id INTEGER;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '';
    RAISE NOTICE 'TENTATIVE D''INSCRIPTION';
    RAISE NOTICE '';
    RAISE NOTICE 'Nom : %', p_nom;
    RAISE NOTICE 'Email : %', p_email;
    RAISE NOTICE '';
    IF p_nom IS NULL OR TRIM(p_nom) = '' THEN
        RAISE EXCEPTION ' ERREUR : Le nom est obligatoire';
    END IF;
    
    IF p_email IS NULL OR TRIM(p_email) = '' THEN
        RAISE EXCEPTION ' ERREUR : L''email est obligatoire';
    END IF;
    
    IF p_password IS NULL OR TRIM(p_password) = '' THEN
        RAISE EXCEPTION ' ERREUR : Le mot de passe est obligatoire';
    END IF;
    
    IF p_password != p_confirm_password THEN
        RAISE EXCEPTION ' ERREUR : Les mots de passe ne correspondent pas';
    END IF;
    
    IF LENGTH(p_password) < 6 THEN
        RAISE EXCEPTION ' ERREUR : Le mot de passe doit contenir au moins 6 caractères';
    END IF;
    
    -- Vérifier si l'email existe déjà
    SELECT EXISTS(SELECT 1 FROM utilisateurs WHERE email = p_email) INTO v_user_exists;
    
    IF v_user_exists THEN
        RAISE EXCEPTION ' ERREUR : Cet email "%" est déjà utilisé', p_email;
    END IF;
    
    -- Utiliser public.crypt et public.gen_salt explicitement
    v_hash := public.crypt(p_password, public.gen_salt('bf'));
    
    INSERT INTO utilisateurs (nom, email, password, role)
    VALUES (p_nom, p_email, v_hash, 'etudiant')
    RETURNING id_user INTO v_new_id;
    
    RAISE NOTICE '';
    RAISE NOTICE ' INSCRIPTION RÉUSSIE !';
    RAISE NOTICE '   ID utilisateur : %', v_new_id;
    RAISE NOTICE '   Nom : %', p_nom;
    RAISE NOTICE '   Email : %', p_email;
    RAISE NOTICE '   Rôle : étudiant';
    RAISE NOTICE '';
    RAISE NOTICE ' Compte créé avec succès, veuillez vous connecter.';
    RAISE NOTICE '';
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '';
        RAISE NOTICE ' ÉCHEC DE L''INSCRIPTION';
        RAISE NOTICE '   Erreur : %', SQLERRM;
        RAISE NOTICE '';
        RAISE;
END;
$$;


ALTER PROCEDURE public.inscription_utilisateur(IN p_nom text, IN p_email text, IN p_password text, IN p_confirm_password text) OWNER TO postgres;

--
-- TOC entry 258 (class 1255 OID 20786)
-- Name: postuler_offre(integer, integer, text, text); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.postuler_offre(p_id_user integer, p_id_stage integer, p_cv_path text, p_lettre_path text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_new_id INTEGER;
    v_offre_titre TEXT;
    v_entreprise_nom TEXT;
    v_check INTEGER;

BEGIN
    SELECT id_stage INTO v_check
    FROM offres_stage
    WHERE id_stage = p_id_stage;

    IF v_check IS NULL THEN
        RAISE EXCEPTION 'Offre non trouvée (ID: %)', p_id_stage;
    END IF;
    SELECT id_user INTO v_check
    FROM utilisateurs
    WHERE id_user = p_id_user AND role = 'etudiant';

    IF v_check IS NULL THEN
        RAISE EXCEPTION 'Étudiant non trouvé (ID: %)', p_id_user;
    END IF;
    SELECT id_candidature INTO v_check
    FROM candidatures
    WHERE id_user = p_id_user AND id_stage = p_id_stage;

    IF v_check IS NOT NULL THEN
        RAISE EXCEPTION 'Vous avez déjà postulé à cette offre';
    END IF;
    IF p_cv_path IS NULL OR p_cv_path = '' THEN
        RAISE EXCEPTION 'CV obligatoire';
    END IF;

    IF p_lettre_path IS NULL OR p_lettre_path = '' THEN
        RAISE EXCEPTION 'Lettre de motivation obligatoire';
    END IF;
    SELECT o.titre, e.nom
    INTO v_offre_titre, v_entreprise_nom
    FROM offres_stage o
    JOIN entreprises e ON o.id_entreprise = e.id_entreprise
    WHERE o.id_stage = p_id_stage;
    INSERT INTO candidatures 
        (date_postulation, statut, id_user, id_stage, cv_url, lettre_motivation)
    VALUES 
        (CURRENT_DATE, 'en_attente', p_id_user, p_id_stage, p_cv_path, p_lettre_path)
    RETURNING id_candidature INTO v_new_id;
    RETURN jsonb_build_object(
        'success', TRUE,
        'message', 'Candidature envoyée avec succès',
        'id_candidature', v_new_id,
        'offre', v_offre_titre,
        'entreprise', v_entreprise_nom,
        'statut', 'en_attente',
        'date', CURRENT_DATE
    );

EXCEPTION
    WHEN OTHERS THEN
        RETURN jsonb_build_object(
            'success', FALSE,
            'message', SQLERRM,
            'error_code', SQLSTATE
        );
END;
$$;


ALTER FUNCTION public.postuler_offre(p_id_user integer, p_id_stage integer, p_cv_path text, p_lettre_path text) OWNER TO postgres;

--
-- TOC entry 257 (class 1255 OID 20783)
-- Name: search_offres_stage(text, text, integer, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.search_offres_stage(p_ville text DEFAULT NULL::text, p_niveau_etude text DEFAULT NULL::text, p_duree_min integer DEFAULT NULL::integer, p_duree_max integer DEFAULT NULL::integer) RETURNS jsonb
    LANGUAGE sql
    AS $$
    SELECT COALESCE(
        jsonb_agg(
            jsonb_build_object(
                'id_stage', o.id_stage,
                'titre', o.titre,
                'niveau_etude', o.niveau_etude,
                'duree_semaine', o.duree_semaine,
                'date_publication', o.date_publication,
                'id_entreprise', e.id_entreprise,
                'entreprise', e.nom,
                'ville', e.ville,
                'logo', e.logo
            )
            ORDER BY o.date_publication DESC
        ),
        '[]'::JSONB
    )
    FROM offres_stage o
    JOIN entreprises e ON o.id_entreprise = e.id_entreprise
    WHERE (p_ville IS NULL OR p_ville = '' OR e.ville ILIKE '%' || p_ville || '%')
      AND (p_niveau_etude IS NULL OR p_niveau_etude = '' OR o.niveau_etude = p_niveau_etude)
      AND (p_duree_min IS NULL OR o.duree_semaine >= p_duree_min)
      AND (p_duree_max IS NULL OR o.duree_semaine <= p_duree_max);
$$;


ALTER FUNCTION public.search_offres_stage(p_ville text, p_niveau_etude text, p_duree_min integer, p_duree_max integer) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 229 (class 1259 OID 18854)
-- Name: avis; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.avis (
    id_avis integer NOT NULL,
    id_entreprise integer,
    id_user integer,
    note integer,
    commentaire text,
    date_avis timestamp without time zone DEFAULT now(),
    CONSTRAINT avis_note_check CHECK (((note >= 1) AND (note <= 5)))
);


ALTER TABLE public.avis OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 18853)
-- Name: avis_id_avis_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.avis_id_avis_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.avis_id_avis_seq OWNER TO postgres;

--
-- TOC entry 5102 (class 0 OID 0)
-- Dependencies: 228
-- Name: avis_id_avis_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.avis_id_avis_seq OWNED BY public.avis.id_avis;


--
-- TOC entry 227 (class 1259 OID 18796)
-- Name: candidatures; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.candidatures (
    id_candidature integer NOT NULL,
    date_postulation timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    statut character varying(20) DEFAULT 'en_attente'::character varying,
    cv_url character varying(255),
    lettre_motivation text,
    id_user integer NOT NULL,
    id_stage integer NOT NULL,
    CONSTRAINT check_statut CHECK (((statut)::text = ANY ((ARRAY['en_attente'::character varying, 'accepte'::character varying, 'refuse'::character varying])::text[])))
);


ALTER TABLE public.candidatures OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 18795)
-- Name: candidatures_id_candidature_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.candidatures_id_candidature_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.candidatures_id_candidature_seq OWNER TO postgres;

--
-- TOC entry 5103 (class 0 OID 0)
-- Dependencies: 226
-- Name: candidatures_id_candidature_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.candidatures_id_candidature_seq OWNED BY public.candidatures.id_candidature;


--
-- TOC entry 223 (class 1259 OID 18777)
-- Name: entreprises; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.entreprises (
    id_entreprise integer NOT NULL,
    nom character varying(100) NOT NULL,
    adresse text,
    email character varying(150),
    ville character varying(100),
    logo text,
    description text,
    secteur character varying(100),
    date_creation date
);


ALTER TABLE public.entreprises OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 18776)
-- Name: entreprises_id_entreprise_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.entreprises_id_entreprise_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.entreprises_id_entreprise_seq OWNER TO postgres;

--
-- TOC entry 5104 (class 0 OID 0)
-- Dependencies: 222
-- Name: entreprises_id_entreprise_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.entreprises_id_entreprise_seq OWNED BY public.entreprises.id_entreprise;


--
-- TOC entry 225 (class 1259 OID 18786)
-- Name: offres_stage; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.offres_stage (
    id_stage integer NOT NULL,
    titre character varying(150) NOT NULL,
    description text,
    date_publication date DEFAULT CURRENT_DATE,
    id_entreprise integer,
    niveau_etude character varying(50),
    duree_semaine integer
);


ALTER TABLE public.offres_stage OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 18785)
-- Name: offres_stage_id_stage_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.offres_stage_id_stage_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.offres_stage_id_stage_seq OWNER TO postgres;

--
-- TOC entry 5105 (class 0 OID 0)
-- Dependencies: 224
-- Name: offres_stage_id_stage_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.offres_stage_id_stage_seq OWNED BY public.offres_stage.id_stage;


--
-- TOC entry 221 (class 1259 OID 18765)
-- Name: utilisateurs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.utilisateurs (
    id_user integer NOT NULL,
    nom character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    role character varying(20) NOT NULL,
    CONSTRAINT check_role CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'etudiant'::character varying])::text[])))
);


ALTER TABLE public.utilisateurs OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 18764)
-- Name: utilisateurs_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.utilisateurs_id_user_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.utilisateurs_id_user_seq OWNER TO postgres;

--
-- TOC entry 5106 (class 0 OID 0)
-- Dependencies: 220
-- Name: utilisateurs_id_user_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.utilisateurs_id_user_seq OWNED BY public.utilisateurs.id_user;


--
-- TOC entry 4925 (class 2604 OID 18857)
-- Name: avis id_avis; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.avis ALTER COLUMN id_avis SET DEFAULT nextval('public.avis_id_avis_seq'::regclass);


--
-- TOC entry 4922 (class 2604 OID 18799)
-- Name: candidatures id_candidature; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidatures ALTER COLUMN id_candidature SET DEFAULT nextval('public.candidatures_id_candidature_seq'::regclass);


--
-- TOC entry 4919 (class 2604 OID 18780)
-- Name: entreprises id_entreprise; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entreprises ALTER COLUMN id_entreprise SET DEFAULT nextval('public.entreprises_id_entreprise_seq'::regclass);


--
-- TOC entry 4920 (class 2604 OID 18789)
-- Name: offres_stage id_stage; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offres_stage ALTER COLUMN id_stage SET DEFAULT nextval('public.offres_stage_id_stage_seq'::regclass);


--
-- TOC entry 4918 (class 2604 OID 18768)
-- Name: utilisateurs id_user; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs ALTER COLUMN id_user SET DEFAULT nextval('public.utilisateurs_id_user_seq'::regclass);


--
-- TOC entry 4943 (class 2606 OID 18864)
-- Name: avis avis_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_pkey PRIMARY KEY (id_avis);


--
-- TOC entry 4939 (class 2606 OID 18812)
-- Name: candidatures pk_candidature; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidatures
    ADD CONSTRAINT pk_candidature PRIMARY KEY (id_candidature);


--
-- TOC entry 4935 (class 2606 OID 18808)
-- Name: entreprises pk_entreprise; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.entreprises
    ADD CONSTRAINT pk_entreprise PRIMARY KEY (id_entreprise);


--
-- TOC entry 4937 (class 2606 OID 18810)
-- Name: offres_stage pk_stage; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offres_stage
    ADD CONSTRAINT pk_stage PRIMARY KEY (id_stage);


--
-- TOC entry 4931 (class 2606 OID 18806)
-- Name: utilisateurs pk_user; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT pk_user PRIMARY KEY (id_user);


--
-- TOC entry 4941 (class 2606 OID 18831)
-- Name: candidatures unique_candidature; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidatures
    ADD CONSTRAINT unique_candidature UNIQUE (id_user, id_stage);


--
-- TOC entry 4933 (class 2606 OID 18829)
-- Name: utilisateurs unique_email; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.utilisateurs
    ADD CONSTRAINT unique_email UNIQUE (email);


--
-- TOC entry 4947 (class 2606 OID 18865)
-- Name: avis avis_id_entreprise_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_id_entreprise_fkey FOREIGN KEY (id_entreprise) REFERENCES public.entreprises(id_entreprise) ON DELETE CASCADE;


--
-- TOC entry 4948 (class 2606 OID 18870)
-- Name: avis avis_id_user_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.avis
    ADD CONSTRAINT avis_id_user_fkey FOREIGN KEY (id_user) REFERENCES public.utilisateurs(id_user) ON DELETE CASCADE;


--
-- TOC entry 4945 (class 2606 OID 18823)
-- Name: candidatures fk_candidature_stage; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidatures
    ADD CONSTRAINT fk_candidature_stage FOREIGN KEY (id_stage) REFERENCES public.offres_stage(id_stage) ON DELETE CASCADE;


--
-- TOC entry 4946 (class 2606 OID 18818)
-- Name: candidatures fk_candidature_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.candidatures
    ADD CONSTRAINT fk_candidature_user FOREIGN KEY (id_user) REFERENCES public.utilisateurs(id_user) ON DELETE CASCADE;


--
-- TOC entry 4944 (class 2606 OID 18813)
-- Name: offres_stage fk_stage_entreprise; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.offres_stage
    ADD CONSTRAINT fk_stage_entreprise FOREIGN KEY (id_entreprise) REFERENCES public.entreprises(id_entreprise) ON DELETE CASCADE;


-- Completed on 2026-11-16 19:52:28

--
-- PostgreSQL database dump complete
--

\unrestrict KI4bCGBOZd0T7TBbGPtf4DGMKUTHyhtuWI69ovznEbQSRypULwd7IaBy9xRd99v

