--
-- PostgreSQL database dump
--

\restrict kmBVNicxr4On6HsrjVfRdYSvH1NxEiOv6jIwLOxLe65N4eJ8Z6uNsJAUeazMHej

-- Dumped from database version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.10 (Ubuntu 16.10-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accounting_transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.accounting_transactions (
    id bigint NOT NULL,
    campaign_reconciliation_id bigint,
    advertising_plan_id bigint,
    description character varying(255) NOT NULL,
    income numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    expense numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    profit numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    reference_number character varying(255),
    client_name character varying(255),
    meta_campaign_id character varying(255),
    campaign_start_date date,
    campaign_end_date date,
    transaction_date date NOT NULL,
    due_date date,
    metadata json,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT accounting_transactions_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'completed'::character varying, 'cancelled'::character varying, 'refunded'::character varying, 'paused'::character varying])::text[])))
);


ALTER TABLE public.accounting_transactions OWNER TO postgres;

--
-- Name: accounting_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.accounting_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.accounting_transactions_id_seq OWNER TO postgres;

--
-- Name: accounting_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.accounting_transactions_id_seq OWNED BY public.accounting_transactions.id;


--
-- Name: active_campaigns_view; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.active_campaigns_view (
    id bigint NOT NULL,
    meta_campaign_id character varying(255) NOT NULL,
    meta_adset_id character varying(255),
    meta_ad_id character varying(255),
    meta_campaign_name character varying(255) NOT NULL,
    meta_adset_name character varying(255),
    meta_ad_name character varying(255),
    campaign_daily_budget numeric(10,2),
    campaign_total_budget numeric(10,2),
    campaign_remaining_budget numeric(10,2),
    adset_daily_budget numeric(10,2),
    adset_lifetime_budget numeric(10,2),
    amount_spent numeric(10,2),
    campaign_start_time timestamp(0) without time zone,
    campaign_stop_time timestamp(0) without time zone,
    campaign_created_time timestamp(0) without time zone,
    adset_start_time timestamp(0) without time zone,
    adset_stop_time timestamp(0) without time zone,
    campaign_status character varying(255) NOT NULL,
    adset_status character varying(255),
    ad_status character varying(255),
    campaign_objective character varying(255),
    facebook_account_id bigint NOT NULL,
    ad_account_id character varying(255) NOT NULL,
    campaign_data json,
    adset_data json,
    ad_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.active_campaigns_view OWNER TO postgres;

--
-- Name: active_campaigns_view_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.active_campaigns_view_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.active_campaigns_view_id_seq OWNER TO postgres;

--
-- Name: active_campaigns_view_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.active_campaigns_view_id_seq OWNED BY public.active_campaigns_view.id;


--
-- Name: advertising_plans; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.advertising_plans (
    id bigint NOT NULL,
    plan_name character varying(255) NOT NULL,
    description text,
    daily_budget numeric(10,2) NOT NULL,
    duration_days integer NOT NULL,
    total_budget numeric(10,2) NOT NULL,
    client_price numeric(10,2) NOT NULL,
    profit_margin numeric(10,2) NOT NULL,
    profit_percentage numeric(5,2) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    features json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.advertising_plans OWNER TO postgres;

--
-- Name: advertising_plans_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.advertising_plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.advertising_plans_id_seq OWNER TO postgres;

--
-- Name: advertising_plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.advertising_plans_id_seq OWNED BY public.advertising_plans.id;


--
-- Name: analysis_histories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.analysis_histories (
    id bigint NOT NULL,
    report_id bigint NOT NULL,
    input_data json NOT NULL,
    analysis_result json NOT NULL,
    performance_metrics json NOT NULL,
    prompt_used text NOT NULL,
    model_version character varying(255) DEFAULT 'gemini-1.5-flash'::character varying NOT NULL,
    tokens_used integer,
    processing_time double precision,
    feedback_data json,
    was_helpful boolean,
    user_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.analysis_histories OWNER TO postgres;

--
-- Name: analysis_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.analysis_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.analysis_histories_id_seq OWNER TO postgres;

--
-- Name: analysis_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.analysis_histories_id_seq OWNED BY public.analysis_histories.id;


--
-- Name: automation_tasks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.automation_tasks (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    facebook_account_id bigint NOT NULL,
    google_sheet_id bigint NOT NULL,
    frequency character varying(255) NOT NULL,
    scheduled_time time(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    last_run timestamp(0) without time zone,
    next_run timestamp(0) without time zone,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT automation_tasks_frequency_check CHECK (((frequency)::text = ANY ((ARRAY['hourly'::character varying, 'daily'::character varying, 'weekly'::character varying, 'monthly'::character varying, 'custom'::character varying])::text[])))
);


ALTER TABLE public.automation_tasks OWNER TO postgres;

--
-- Name: automation_tasks_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.automation_tasks_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.automation_tasks_id_seq OWNER TO postgres;

--
-- Name: automation_tasks_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.automation_tasks_id_seq OWNED BY public.automation_tasks.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: campaign_plan_reconciliations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.campaign_plan_reconciliations (
    id bigint NOT NULL,
    active_campaign_id bigint NOT NULL,
    advertising_plan_id bigint,
    reconciliation_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    reconciliation_date timestamp(0) without time zone,
    notes text,
    planned_budget numeric(10,2),
    actual_spent numeric(10,2),
    variance numeric(10,2),
    variance_percentage numeric(8,2),
    reconciliation_data json,
    last_updated_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT campaign_plan_reconciliations_reconciliation_status_check CHECK (((reconciliation_status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying, 'completed'::character varying, 'paused'::character varying])::text[])))
);


ALTER TABLE public.campaign_plan_reconciliations OWNER TO postgres;

--
-- Name: campaign_plan_reconciliations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.campaign_plan_reconciliations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.campaign_plan_reconciliations_id_seq OWNER TO postgres;

--
-- Name: campaign_plan_reconciliations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.campaign_plan_reconciliations_id_seq OWNED BY public.campaign_plan_reconciliations.id;


--
-- Name: exchange_rates; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.exchange_rates (
    id bigint NOT NULL,
    currency_code character varying(10) NOT NULL,
    rate numeric(15,8) NOT NULL,
    source character varying(20) NOT NULL,
    target_currency character varying(10) DEFAULT 'VES'::character varying NOT NULL,
    binance_equivalent numeric(15,8),
    bcv_equivalent numeric(15,8),
    conversion_factor numeric(10,6),
    fetched_at timestamp(0) without time zone NOT NULL,
    is_valid boolean DEFAULT true NOT NULL,
    error_message text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.exchange_rates OWNER TO postgres;

--
-- Name: exchange_rates_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.exchange_rates_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.exchange_rates_id_seq OWNER TO postgres;

--
-- Name: exchange_rates_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.exchange_rates_id_seq OWNED BY public.exchange_rates.id;


--
-- Name: facebook_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.facebook_accounts (
    id bigint NOT NULL,
    account_name character varying(255) NOT NULL,
    app_id character varying(255) NOT NULL,
    app_secret character varying(255) NOT NULL,
    access_token text NOT NULL,
    token_expires_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    settings json,
    selected_ad_account_id character varying(255),
    selected_page_id character varying(255),
    selected_campaign_ids json,
    selected_ad_ids json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.facebook_accounts OWNER TO postgres;

--
-- Name: facebook_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.facebook_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.facebook_accounts_id_seq OWNER TO postgres;

--
-- Name: facebook_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.facebook_accounts_id_seq OWNED BY public.facebook_accounts.id;


--
-- Name: facebook_campaigns; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.facebook_campaigns (
    id bigint NOT NULL,
    facebook_account_id bigint NOT NULL,
    campaign_id character varying(255) NOT NULL,
    campaign_name character varying(255) NOT NULL,
    campaign_status character varying(255) DEFAULT 'ACTIVE'::character varying NOT NULL,
    statistics json,
    date_start date,
    date_stop date,
    date_range character varying(255),
    last_updated timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.facebook_campaigns OWNER TO postgres;

--
-- Name: facebook_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.facebook_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.facebook_campaigns_id_seq OWNER TO postgres;

--
-- Name: facebook_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.facebook_campaigns_id_seq OWNED BY public.facebook_campaigns.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: google_sheets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.google_sheets (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    spreadsheet_id character varying(255) NOT NULL,
    worksheet_name character varying(255) NOT NULL,
    cell_mapping json NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    settings json,
    individual_ads boolean DEFAULT false NOT NULL,
    start_row integer DEFAULT 2 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.google_sheets OWNER TO postgres;

--
-- Name: google_sheets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.google_sheets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.google_sheets_id_seq OWNER TO postgres;

--
-- Name: google_sheets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.google_sheets_id_seq OWNED BY public.google_sheets.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: queue_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.queue_jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL,
    job_type character varying(255),
    job_data json,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    error_message text,
    execution_time integer
);


ALTER TABLE public.queue_jobs OWNER TO postgres;

--
-- Name: queue_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.queue_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.queue_jobs_id_seq OWNER TO postgres;

--
-- Name: queue_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.queue_jobs_id_seq OWNED BY public.queue_jobs.id;


--
-- Name: report_brands; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_brands (
    id bigint NOT NULL,
    report_id bigint NOT NULL,
    brand_name character varying(255) NOT NULL,
    brand_identifier character varying(255),
    campaign_ids json,
    brand_settings json,
    slide_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.report_brands OWNER TO postgres;

--
-- Name: report_brands_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_brands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_brands_id_seq OWNER TO postgres;

--
-- Name: report_brands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_brands_id_seq OWNED BY public.report_brands.id;


--
-- Name: report_campaigns; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_campaigns (
    id bigint NOT NULL,
    report_id bigint NOT NULL,
    report_brand_id bigint,
    campaign_id character varying(255) NOT NULL,
    campaign_name character varying(500) NOT NULL,
    ad_account_id character varying(255) NOT NULL,
    campaign_data json,
    statistics json,
    ad_image_url text,
    ad_image_local_path text,
    slide_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.report_campaigns OWNER TO postgres;

--
-- Name: report_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_campaigns_id_seq OWNER TO postgres;

--
-- Name: report_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_campaigns_id_seq OWNED BY public.report_campaigns.id;


--
-- Name: report_facebook_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.report_facebook_accounts (
    id bigint NOT NULL,
    report_id bigint NOT NULL,
    facebook_account_id bigint NOT NULL,
    settings json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.report_facebook_accounts OWNER TO postgres;

--
-- Name: report_facebook_accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.report_facebook_accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.report_facebook_accounts_id_seq OWNER TO postgres;

--
-- Name: report_facebook_accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.report_facebook_accounts_id_seq OWNED BY public.report_facebook_accounts.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reports (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    period_start date NOT NULL,
    period_end date NOT NULL,
    selected_facebook_accounts json,
    selected_campaigns json,
    brands_config json,
    statistics_config json,
    charts_config json,
    generated_data json,
    google_slides_url character varying(255),
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    generated_at timestamp(0) without time zone,
    settings json,
    is_active boolean DEFAULT true NOT NULL,
    pdf_generated boolean DEFAULT false NOT NULL,
    pdf_url character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.reports OWNER TO postgres;

--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_id_seq OWNER TO postgres;

--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: task_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.task_logs (
    id bigint NOT NULL,
    automation_task_id bigint NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    completed_at timestamp(0) without time zone,
    status character varying(255) NOT NULL,
    message text,
    error_message text,
    records_processed integer DEFAULT 0 NOT NULL,
    execution_time double precision DEFAULT '0'::double precision NOT NULL,
    data_synced json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT task_logs_status_check CHECK (((status)::text = ANY ((ARRAY['running'::character varying, 'success'::character varying, 'failed'::character varying])::text[])))
);


ALTER TABLE public.task_logs OWNER TO postgres;

--
-- Name: task_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.task_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.task_logs_id_seq OWNER TO postgres;

--
-- Name: task_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.task_logs_id_seq OWNED BY public.task_logs.id;


--
-- Name: telegram_campaigns; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.telegram_campaigns (
    id bigint NOT NULL,
    telegram_user_id character varying(255) NOT NULL,
    telegram_conversation_id character varying(255),
    campaign_name character varying(255) NOT NULL,
    objective character varying(255) NOT NULL,
    budget_type character varying(255) NOT NULL,
    daily_budget numeric(10,2) NOT NULL,
    start_date date NOT NULL,
    end_date date,
    targeting_data json,
    ad_data json,
    media_type character varying(255),
    media_url character varying(255),
    ad_copy text,
    meta_campaign_id character varying(255),
    meta_adset_id character varying(255),
    meta_ad_id character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.telegram_campaigns OWNER TO postgres;

--
-- Name: telegram_campaigns_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.telegram_campaigns_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.telegram_campaigns_id_seq OWNER TO postgres;

--
-- Name: telegram_campaigns_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.telegram_campaigns_id_seq OWNED BY public.telegram_campaigns.id;


--
-- Name: telegram_conversations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.telegram_conversations (
    id bigint NOT NULL,
    telegram_user_id character varying(255) NOT NULL,
    telegram_username character varying(255),
    telegram_first_name character varying(255),
    telegram_last_name character varying(255),
    current_step character varying(255) DEFAULT 'start'::character varying NOT NULL,
    conversation_data json,
    is_active boolean DEFAULT true NOT NULL,
    last_activity timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.telegram_conversations OWNER TO postgres;

--
-- Name: telegram_conversations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.telegram_conversations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.telegram_conversations_id_seq OWNER TO postgres;

--
-- Name: telegram_conversations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.telegram_conversations_id_seq OWNED BY public.telegram_conversations.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: accounting_transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accounting_transactions ALTER COLUMN id SET DEFAULT nextval('public.accounting_transactions_id_seq'::regclass);


--
-- Name: active_campaigns_view id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.active_campaigns_view ALTER COLUMN id SET DEFAULT nextval('public.active_campaigns_view_id_seq'::regclass);


--
-- Name: advertising_plans id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.advertising_plans ALTER COLUMN id SET DEFAULT nextval('public.advertising_plans_id_seq'::regclass);


--
-- Name: analysis_histories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.analysis_histories ALTER COLUMN id SET DEFAULT nextval('public.analysis_histories_id_seq'::regclass);


--
-- Name: automation_tasks id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.automation_tasks ALTER COLUMN id SET DEFAULT nextval('public.automation_tasks_id_seq'::regclass);


--
-- Name: campaign_plan_reconciliations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.campaign_plan_reconciliations ALTER COLUMN id SET DEFAULT nextval('public.campaign_plan_reconciliations_id_seq'::regclass);


--
-- Name: exchange_rates id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates ALTER COLUMN id SET DEFAULT nextval('public.exchange_rates_id_seq'::regclass);


--
-- Name: facebook_accounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_accounts ALTER COLUMN id SET DEFAULT nextval('public.facebook_accounts_id_seq'::regclass);


--
-- Name: facebook_campaigns id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_campaigns ALTER COLUMN id SET DEFAULT nextval('public.facebook_campaigns_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: google_sheets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.google_sheets ALTER COLUMN id SET DEFAULT nextval('public.google_sheets_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: queue_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.queue_jobs ALTER COLUMN id SET DEFAULT nextval('public.queue_jobs_id_seq'::regclass);


--
-- Name: report_brands id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_brands ALTER COLUMN id SET DEFAULT nextval('public.report_brands_id_seq'::regclass);


--
-- Name: report_campaigns id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_campaigns ALTER COLUMN id SET DEFAULT nextval('public.report_campaigns_id_seq'::regclass);


--
-- Name: report_facebook_accounts id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_facebook_accounts ALTER COLUMN id SET DEFAULT nextval('public.report_facebook_accounts_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: task_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.task_logs ALTER COLUMN id SET DEFAULT nextval('public.task_logs_id_seq'::regclass);


--
-- Name: telegram_campaigns id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telegram_campaigns ALTER COLUMN id SET DEFAULT nextval('public.telegram_campaigns_id_seq'::regclass);


--
-- Name: telegram_conversations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telegram_conversations ALTER COLUMN id SET DEFAULT nextval('public.telegram_conversations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: accounting_transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.accounting_transactions (id, campaign_reconciliation_id, advertising_plan_id, description, income, expense, profit, currency, status, reference_number, client_name, meta_campaign_id, campaign_start_date, campaign_end_date, transaction_date, due_date, metadata, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: active_campaigns_view; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.active_campaigns_view (id, meta_campaign_id, meta_adset_id, meta_ad_id, meta_campaign_name, meta_adset_name, meta_ad_name, campaign_daily_budget, campaign_total_budget, campaign_remaining_budget, adset_daily_budget, adset_lifetime_budget, amount_spent, campaign_start_time, campaign_stop_time, campaign_created_time, adset_start_time, adset_stop_time, campaign_status, adset_status, ad_status, campaign_objective, facebook_account_id, ad_account_id, campaign_data, adset_data, ad_data, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: advertising_plans; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.advertising_plans (id, plan_name, description, daily_budget, duration_days, total_budget, client_price, profit_margin, profit_percentage, is_active, features, created_at, updated_at) FROM stdin;
1	Plan Básico 3 Días	Plan de $1 diarios por 3 días	1.00	3	3.00	9.00	6.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
2	Plan Básico 4 Días	Plan de $1 diarios por 4 días	1.00	4	4.00	12.00	8.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
3	Plan Básico 5 Días	Plan de $1 diarios por 5 días	1.00	5	5.00	15.00	10.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
4	Plan Básico 6 Días	Plan de $1 diarios por 6 días	1.00	6	6.00	18.00	12.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
5	Plan Básico 7 Días	Plan de $1 diarios por 7 días	1.00	7	7.00	21.00	14.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
6	Plan Básico 8 Días	Plan de $1 diarios por 8 días	1.00	8	8.00	24.00	16.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
7	Plan Básico 9 Días	Plan de $1 diarios por 9 días	1.00	9	9.00	27.00	18.00	200.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
8	Plan Básico 10 Días	Plan de $1 diarios por 10 días	1.00	10	10.00	29.00	19.00	190.00	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
9	Plan Básico 15 Días	Plan de $1 diarios por 15 días	1.00	15	15.00	44.00	29.00	193.30	t	["Facebook Ads","Instagram Ads","Reportes b\\u00e1sicos"]	2025-09-29 13:29:51	2025-09-29 13:29:51
10	Plan Intermedio 3 Días	Plan de $2 diarios por 3 días	2.00	3	6.00	16.00	10.00	166.70	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
11	Plan Intermedio 4 Días	Plan de $2 diarios por 4 días	2.00	4	8.00	19.00	11.00	137.50	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
12	Plan Intermedio 5 Días	Plan de $2 diarios por 5 días	2.00	5	10.00	22.00	12.00	120.00	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
13	Plan Intermedio 6 Días	Plan de $2 diarios por 6 días	2.00	6	12.00	27.00	15.00	125.00	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
14	Plan Intermedio 7 Días	Plan de $2 diarios por 7 días	2.00	7	14.00	29.00	15.00	107.10	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
15	Plan Intermedio 8 Días	Plan de $2 diarios por 8 días	2.00	8	16.00	35.00	19.00	118.80	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
16	Plan Intermedio 9 Días	Plan de $2 diarios por 9 días	2.00	9	18.00	38.00	20.00	111.10	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
17	Plan Intermedio 10 Días	Plan de $2 diarios por 10 días	2.00	10	20.00	47.00	27.00	135.00	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
18	Plan Intermedio 15 Días	Plan de $2 diarios por 15 días	2.00	15	30.00	66.00	36.00	120.00	t	["Facebook Ads","Instagram Ads","Reportes avanzados","Optimizaci\\u00f3n"]	2025-09-29 13:29:51	2025-09-29 13:29:51
19	Plan Premium 3 Días	Plan de $3 diarios por 3 días	3.00	3	9.00	22.00	13.00	144.40	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
20	Plan Premium 4 Días	Plan de $3 diarios por 4 días	3.00	4	12.00	27.00	15.00	125.00	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
21	Plan Premium 5 Días	Plan de $3 diarios por 5 días	3.00	5	15.00	32.00	17.00	113.30	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
22	Plan Premium 6 Días	Plan de $3 diarios por 6 días	3.00	6	18.00	37.00	19.00	105.60	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
23	Plan Premium 7 Días	Plan de $3 diarios por 7 días	3.00	7	21.00	43.00	22.00	104.80	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
24	Plan Premium 8 Días	Plan de $3 diarios por 8 días	3.00	8	24.00	47.00	23.00	95.80	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
25	Plan Premium 9 Días	Plan de $3 diarios por 9 días	3.00	9	27.00	53.00	26.00	96.30	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
26	Plan Premium 10 Días	Plan de $3 diarios por 10 días	3.00	10	30.00	57.00	27.00	90.00	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
27	Plan Premium 15 Días	Plan de $3 diarios por 15 días	3.00	15	45.00	84.00	39.00	86.70	t	["Facebook Ads","Instagram Ads","Reportes premium","Optimizaci\\u00f3n avanzada","Soporte prioritario"]	2025-09-29 13:29:51	2025-09-29 13:29:51
\.


--
-- Data for Name: analysis_histories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.analysis_histories (id, report_id, input_data, analysis_result, performance_metrics, prompt_used, model_version, tokens_used, processing_time, feedback_data, was_helpful, user_notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: automation_tasks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.automation_tasks (id, name, description, facebook_account_id, google_sheet_id, frequency, scheduled_time, is_active, last_run, next_run, settings, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: campaign_plan_reconciliations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.campaign_plan_reconciliations (id, active_campaign_id, advertising_plan_id, reconciliation_status, reconciliation_date, notes, planned_budget, actual_spent, variance, variance_percentage, reconciliation_data, last_updated_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: exchange_rates; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.exchange_rates (id, currency_code, rate, source, target_currency, binance_equivalent, bcv_equivalent, conversion_factor, fetched_at, is_valid, error_message, metadata, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: facebook_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.facebook_accounts (id, account_name, app_id, app_secret, access_token, token_expires_at, is_active, settings, selected_ad_account_id, selected_page_id, selected_campaign_ids, selected_ad_ids, created_at, updated_at) FROM stdin;
1	ADMETRICAS.COM - Cuenta Principal	738576925677923	78f022c605d18b045bf85f73460516e2	EAAKfu1dLWWMBPTGJ97yqnUqnDoa0iC7xson0ZCgJgO86aPAJav8Wav3TJXuGEUV6vxh8zo5lOAOLZANCtr0uaTPExyNzwk6Deeb3No2vPaNZAs6CH8X5kyHE9M7Gp38uRxTV6qkZAZBZC6wZC4mB46AJjtlLZCmEuSNTgFUYQ6ESNnpww0YZB0EIOUob5ZACSZBr9xnt9Fm0oC7AquBjgZDZD	\N	f	{"auto_sync":true,"sync_frequency":"daily","notifications":true,"default_currency":"USD","timezone":"America\\/Caracas"}	\N	\N	\N	\N	2025-09-29 13:29:51	2025-09-29 13:29:51
2	TOKEN ADMETRICAS - App Activa	808947008240397	570c6a1ab1ab8571b59a82f5088e46ca	EAALfu6cRew0BPWUzqBszQdmByLldZCOXY6eZCFUyX5H9iPUHZBNik9CzEYd0EU9YIWc237o1AcFKq60t8Aw6TzKZBf0lA4fZCzMdAjgA7pRoRGVU2E7OgCZAezpEjRyCnbBk7vi3sCFhQkQW0RTVkajwBRzxFnoEUvLhUUlcIMejnFb9AyeCIR98fhHqCec6beksmIhb2JPAZDZD	2025-11-28 13:29:51	t	{"auto_sync":true,"sync_frequency":"daily","notifications":true,"default_currency":"USD","timezone":"America\\/Caracas"}	\N	\N	\N	\N	2025-09-29 13:29:51	2025-09-29 13:29:51
\.


--
-- Data for Name: facebook_campaigns; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.facebook_campaigns (id, facebook_account_id, campaign_id, campaign_name, campaign_status, statistics, date_start, date_stop, date_range, last_updated, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: google_sheets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.google_sheets (id, name, spreadsheet_id, worksheet_name, cell_mapping, is_active, settings, individual_ads, start_row, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_08_14_154014_create_facebook_accounts_table	1
5	2025_08_14_154025_create_google_sheets_table	1
6	2025_08_14_154037_create_automation_tasks_table	1
7	2025_08_14_154045_create_task_logs_table	1
8	2025_08_15_181400_create_queue_jobs_table	1
9	2025_08_15_195605_create_facebook_campaigns_table	1
10	2025_08_21_153410_create_reports_table	1
11	2025_08_21_153424_create_report_brands_table	1
12	2025_08_21_153429_create_report_campaigns_table	1
13	2025_08_21_153631_create_report_facebook_accounts_table	1
14	2025_08_26_134214_create_analysis_histories_table	1
15	2025_09_02_194244_create_advertising_plans_table	1
16	2025_09_03_173807_create_active_campaigns_view_table	1
17	2025_09_04_183442_create_campaign_plan_reconciliations_table	1
18	2025_09_04_184237_create_accounting_transactions_table	1
19	2025_09_10_153124_create_exchange_rates_table	1
20	2025_09_12_152749_create_telegram_conversations_table	1
21	2025_09_12_152756_create_telegram_campaigns_table	1
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: queue_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.queue_jobs (id, queue, payload, attempts, reserved_at, available_at, created_at, job_type, job_data, status, started_at, completed_at, error_message, execution_time) FROM stdin;
\.


--
-- Data for Name: report_brands; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_brands (id, report_id, brand_name, brand_identifier, campaign_ids, brand_settings, slide_order, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: report_campaigns; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_campaigns (id, report_id, report_brand_id, campaign_id, campaign_name, ad_account_id, campaign_data, statistics, ad_image_url, ad_image_local_path, slide_order, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: report_facebook_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.report_facebook_accounts (id, report_id, facebook_account_id, settings, is_active, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: reports; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reports (id, name, description, period_start, period_end, selected_facebook_accounts, selected_campaigns, brands_config, statistics_config, charts_config, generated_data, google_slides_url, status, generated_at, settings, is_active, pdf_generated, pdf_url, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
D527FuA9UghtgikasTMHHgB4oXzaQeVe16w8sZlv	1	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUTBzM3RvWnpLU1RjTGx4QnFkallBNE5MS1hMSkltT3c0R2g4WFhmNCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiROQTIydzRYcjQ4RkFTb214UFBTb1FPSnJtc05FRy9DSW1jTkVwMDc5VkJQaE16L0JTajNBNiI7fQ==	1759152756
7gLXGbb59jKk7j9c0tQ4NjQxM41GKRqxSrDYTHlU	\N	127.0.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoidDJYT09ZNHVGVHdNcElEdGd4MWgyWHdjcmdEUnRTOXdZSmcwNlBQZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7fX0=	1759152822
\.


--
-- Data for Name: task_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.task_logs (id, automation_task_id, started_at, completed_at, status, message, error_message, records_processed, execution_time, data_synced, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: telegram_campaigns; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.telegram_campaigns (id, telegram_user_id, telegram_conversation_id, campaign_name, objective, budget_type, daily_budget, start_date, end_date, targeting_data, ad_data, media_type, media_url, ad_copy, meta_campaign_id, meta_adset_id, meta_ad_id, status, error_message, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: telegram_conversations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.telegram_conversations (id, telegram_user_id, telegram_username, telegram_first_name, telegram_last_name, current_step, conversation_data, is_active, last_activity, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at) FROM stdin;
1	Test User	test@example.com	\N	$2y$12$NA22w4Xr48FASomxPPSoQOJrmsNEG/CImcNEp079VBPhMz/BSj3A6	\N	2025-09-29 13:29:51	2025-09-29 13:29:51
\.


--
-- Name: accounting_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.accounting_transactions_id_seq', 1, false);


--
-- Name: active_campaigns_view_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.active_campaigns_view_id_seq', 1, false);


--
-- Name: advertising_plans_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.advertising_plans_id_seq', 27, true);


--
-- Name: analysis_histories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.analysis_histories_id_seq', 1, false);


--
-- Name: automation_tasks_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.automation_tasks_id_seq', 1, false);


--
-- Name: campaign_plan_reconciliations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.campaign_plan_reconciliations_id_seq', 1, false);


--
-- Name: exchange_rates_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.exchange_rates_id_seq', 1, false);


--
-- Name: facebook_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.facebook_accounts_id_seq', 2, true);


--
-- Name: facebook_campaigns_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.facebook_campaigns_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: google_sheets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.google_sheets_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 21, true);


--
-- Name: queue_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.queue_jobs_id_seq', 1, false);


--
-- Name: report_brands_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_brands_id_seq', 1, false);


--
-- Name: report_campaigns_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_campaigns_id_seq', 1, false);


--
-- Name: report_facebook_accounts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.report_facebook_accounts_id_seq', 1, false);


--
-- Name: reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reports_id_seq', 1, false);


--
-- Name: task_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.task_logs_id_seq', 1, false);


--
-- Name: telegram_campaigns_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.telegram_campaigns_id_seq', 1, false);


--
-- Name: telegram_conversations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.telegram_conversations_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: accounting_transactions accounting_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accounting_transactions
    ADD CONSTRAINT accounting_transactions_pkey PRIMARY KEY (id);


--
-- Name: active_campaigns_view active_campaigns_view_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.active_campaigns_view
    ADD CONSTRAINT active_campaigns_view_pkey PRIMARY KEY (id);


--
-- Name: advertising_plans advertising_plans_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.advertising_plans
    ADD CONSTRAINT advertising_plans_pkey PRIMARY KEY (id);


--
-- Name: analysis_histories analysis_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.analysis_histories
    ADD CONSTRAINT analysis_histories_pkey PRIMARY KEY (id);


--
-- Name: automation_tasks automation_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.automation_tasks
    ADD CONSTRAINT automation_tasks_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: campaign_plan_reconciliations campaign_plan_reconciliations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.campaign_plan_reconciliations
    ADD CONSTRAINT campaign_plan_reconciliations_pkey PRIMARY KEY (id);


--
-- Name: exchange_rates exchange_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates
    ADD CONSTRAINT exchange_rates_pkey PRIMARY KEY (id);


--
-- Name: facebook_accounts facebook_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_accounts
    ADD CONSTRAINT facebook_accounts_pkey PRIMARY KEY (id);


--
-- Name: facebook_campaigns facebook_campaigns_facebook_account_id_campaign_id_date_range_u; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_campaigns
    ADD CONSTRAINT facebook_campaigns_facebook_account_id_campaign_id_date_range_u UNIQUE (facebook_account_id, campaign_id, date_range);


--
-- Name: facebook_campaigns facebook_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_campaigns
    ADD CONSTRAINT facebook_campaigns_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: google_sheets google_sheets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.google_sheets
    ADD CONSTRAINT google_sheets_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: queue_jobs queue_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.queue_jobs
    ADD CONSTRAINT queue_jobs_pkey PRIMARY KEY (id);


--
-- Name: report_brands report_brands_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_brands
    ADD CONSTRAINT report_brands_pkey PRIMARY KEY (id);


--
-- Name: report_campaigns report_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_campaigns
    ADD CONSTRAINT report_campaigns_pkey PRIMARY KEY (id);


--
-- Name: report_facebook_accounts report_facebook_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_facebook_accounts
    ADD CONSTRAINT report_facebook_accounts_pkey PRIMARY KEY (id);


--
-- Name: report_facebook_accounts report_facebook_accounts_report_id_facebook_account_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_facebook_accounts
    ADD CONSTRAINT report_facebook_accounts_report_id_facebook_account_id_unique UNIQUE (report_id, facebook_account_id);


--
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: task_logs task_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.task_logs
    ADD CONSTRAINT task_logs_pkey PRIMARY KEY (id);


--
-- Name: telegram_campaigns telegram_campaigns_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telegram_campaigns
    ADD CONSTRAINT telegram_campaigns_pkey PRIMARY KEY (id);


--
-- Name: telegram_conversations telegram_conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.telegram_conversations
    ADD CONSTRAINT telegram_conversations_pkey PRIMARY KEY (id);


--
-- Name: accounting_transactions unique_campaign_transaction; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accounting_transactions
    ADD CONSTRAINT unique_campaign_transaction UNIQUE (campaign_reconciliation_id, meta_campaign_id);


--
-- Name: exchange_rates unique_rate_per_source_time; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.exchange_rates
    ADD CONSTRAINT unique_rate_per_source_time UNIQUE (currency_code, source, fetched_at);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: accounting_transactions_campaign_end_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_campaign_end_date_index ON public.accounting_transactions USING btree (campaign_end_date);


--
-- Name: accounting_transactions_campaign_start_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_campaign_start_date_index ON public.accounting_transactions USING btree (campaign_start_date);


--
-- Name: accounting_transactions_client_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_client_name_index ON public.accounting_transactions USING btree (client_name);


--
-- Name: accounting_transactions_due_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_due_date_index ON public.accounting_transactions USING btree (due_date);


--
-- Name: accounting_transactions_meta_campaign_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_meta_campaign_id_index ON public.accounting_transactions USING btree (meta_campaign_id);


--
-- Name: accounting_transactions_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_status_index ON public.accounting_transactions USING btree (status);


--
-- Name: accounting_transactions_transaction_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX accounting_transactions_transaction_date_index ON public.accounting_transactions USING btree (transaction_date);


--
-- Name: active_campaigns_view_campaign_start_time_campaign_stop_time_in; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX active_campaigns_view_campaign_start_time_campaign_stop_time_in ON public.active_campaigns_view USING btree (campaign_start_time, campaign_stop_time);


--
-- Name: active_campaigns_view_campaign_status_adset_status_ad_status_in; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX active_campaigns_view_campaign_status_adset_status_ad_status_in ON public.active_campaigns_view USING btree (campaign_status, adset_status, ad_status);


--
-- Name: active_campaigns_view_facebook_account_id_ad_account_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX active_campaigns_view_facebook_account_id_ad_account_id_index ON public.active_campaigns_view USING btree (facebook_account_id, ad_account_id);


--
-- Name: active_campaigns_view_meta_campaign_id_meta_adset_id_meta_ad_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX active_campaigns_view_meta_campaign_id_meta_adset_id_meta_ad_id ON public.active_campaigns_view USING btree (meta_campaign_id, meta_adset_id, meta_ad_id);


--
-- Name: advertising_plans_is_active_daily_budget_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX advertising_plans_is_active_daily_budget_index ON public.advertising_plans USING btree (is_active, daily_budget);


--
-- Name: advertising_plans_total_budget_client_price_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX advertising_plans_total_budget_client_price_index ON public.advertising_plans USING btree (total_budget, client_price);


--
-- Name: analysis_histories_model_version_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX analysis_histories_model_version_index ON public.analysis_histories USING btree (model_version);


--
-- Name: analysis_histories_report_id_created_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX analysis_histories_report_id_created_at_index ON public.analysis_histories USING btree (report_id, created_at);


--
-- Name: analysis_histories_was_helpful_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX analysis_histories_was_helpful_index ON public.analysis_histories USING btree (was_helpful);


--
-- Name: automation_tasks_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX automation_tasks_is_active_index ON public.automation_tasks USING btree (is_active);


--
-- Name: automation_tasks_next_run_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX automation_tasks_next_run_is_active_index ON public.automation_tasks USING btree (next_run, is_active);


--
-- Name: campaign_plan_reconciliations_active_campaign_id_advertising_pl; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX campaign_plan_reconciliations_active_campaign_id_advertising_pl ON public.campaign_plan_reconciliations USING btree (active_campaign_id, advertising_plan_id);


--
-- Name: campaign_plan_reconciliations_advertising_plan_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX campaign_plan_reconciliations_advertising_plan_id_index ON public.campaign_plan_reconciliations USING btree (advertising_plan_id);


--
-- Name: campaign_plan_reconciliations_reconciliation_date_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX campaign_plan_reconciliations_reconciliation_date_index ON public.campaign_plan_reconciliations USING btree (reconciliation_date);


--
-- Name: campaign_plan_reconciliations_reconciliation_status_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX campaign_plan_reconciliations_reconciliation_status_index ON public.campaign_plan_reconciliations USING btree (reconciliation_status);


--
-- Name: exchange_rates_currency_code_is_valid_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX exchange_rates_currency_code_is_valid_index ON public.exchange_rates USING btree (currency_code, is_valid);


--
-- Name: exchange_rates_currency_code_source_fetched_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX exchange_rates_currency_code_source_fetched_at_index ON public.exchange_rates USING btree (currency_code, source, fetched_at);


--
-- Name: exchange_rates_source_is_valid_fetched_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX exchange_rates_source_is_valid_fetched_at_index ON public.exchange_rates USING btree (source, is_valid, fetched_at);


--
-- Name: facebook_campaigns_campaign_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX facebook_campaigns_campaign_id_index ON public.facebook_campaigns USING btree (campaign_id);


--
-- Name: facebook_campaigns_campaign_status_last_updated_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX facebook_campaigns_campaign_status_last_updated_index ON public.facebook_campaigns USING btree (campaign_status, last_updated);


--
-- Name: facebook_campaigns_date_start_date_stop_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX facebook_campaigns_date_start_date_stop_index ON public.facebook_campaigns USING btree (date_start, date_stop);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: queue_jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX queue_jobs_queue_index ON public.queue_jobs USING btree (queue);


--
-- Name: queue_jobs_queue_reserved_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX queue_jobs_queue_reserved_at_index ON public.queue_jobs USING btree (queue, reserved_at);


--
-- Name: report_facebook_accounts_report_id_is_active_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX report_facebook_accounts_report_id_is_active_index ON public.report_facebook_accounts USING btree (report_id, is_active);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: task_logs_automation_task_id_started_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX task_logs_automation_task_id_started_at_index ON public.task_logs USING btree (automation_task_id, started_at);


--
-- Name: task_logs_status_started_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX task_logs_status_started_at_index ON public.task_logs USING btree (status, started_at);


--
-- Name: telegram_campaigns_telegram_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telegram_campaigns_telegram_user_id_index ON public.telegram_campaigns USING btree (telegram_user_id);


--
-- Name: telegram_conversations_telegram_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX telegram_conversations_telegram_user_id_index ON public.telegram_conversations USING btree (telegram_user_id);


--
-- Name: accounting_transactions accounting_transactions_advertising_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accounting_transactions
    ADD CONSTRAINT accounting_transactions_advertising_plan_id_foreign FOREIGN KEY (advertising_plan_id) REFERENCES public.advertising_plans(id) ON DELETE SET NULL;


--
-- Name: accounting_transactions accounting_transactions_campaign_reconciliation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.accounting_transactions
    ADD CONSTRAINT accounting_transactions_campaign_reconciliation_id_foreign FOREIGN KEY (campaign_reconciliation_id) REFERENCES public.campaign_plan_reconciliations(id) ON DELETE SET NULL;


--
-- Name: active_campaigns_view active_campaigns_view_facebook_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.active_campaigns_view
    ADD CONSTRAINT active_campaigns_view_facebook_account_id_foreign FOREIGN KEY (facebook_account_id) REFERENCES public.facebook_accounts(id) ON DELETE CASCADE;


--
-- Name: analysis_histories analysis_histories_report_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.analysis_histories
    ADD CONSTRAINT analysis_histories_report_id_foreign FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: automation_tasks automation_tasks_facebook_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.automation_tasks
    ADD CONSTRAINT automation_tasks_facebook_account_id_foreign FOREIGN KEY (facebook_account_id) REFERENCES public.facebook_accounts(id) ON DELETE CASCADE;


--
-- Name: automation_tasks automation_tasks_google_sheet_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.automation_tasks
    ADD CONSTRAINT automation_tasks_google_sheet_id_foreign FOREIGN KEY (google_sheet_id) REFERENCES public.google_sheets(id) ON DELETE CASCADE;


--
-- Name: campaign_plan_reconciliations campaign_plan_reconciliations_active_campaign_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.campaign_plan_reconciliations
    ADD CONSTRAINT campaign_plan_reconciliations_active_campaign_id_foreign FOREIGN KEY (active_campaign_id) REFERENCES public.active_campaigns_view(id) ON DELETE CASCADE;


--
-- Name: campaign_plan_reconciliations campaign_plan_reconciliations_advertising_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.campaign_plan_reconciliations
    ADD CONSTRAINT campaign_plan_reconciliations_advertising_plan_id_foreign FOREIGN KEY (advertising_plan_id) REFERENCES public.advertising_plans(id) ON DELETE SET NULL;


--
-- Name: facebook_campaigns facebook_campaigns_facebook_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facebook_campaigns
    ADD CONSTRAINT facebook_campaigns_facebook_account_id_foreign FOREIGN KEY (facebook_account_id) REFERENCES public.facebook_accounts(id) ON DELETE CASCADE;


--
-- Name: report_brands report_brands_report_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_brands
    ADD CONSTRAINT report_brands_report_id_foreign FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: report_campaigns report_campaigns_report_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_campaigns
    ADD CONSTRAINT report_campaigns_report_brand_id_foreign FOREIGN KEY (report_brand_id) REFERENCES public.report_brands(id) ON DELETE CASCADE;


--
-- Name: report_campaigns report_campaigns_report_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_campaigns
    ADD CONSTRAINT report_campaigns_report_id_foreign FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: report_facebook_accounts report_facebook_accounts_facebook_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_facebook_accounts
    ADD CONSTRAINT report_facebook_accounts_facebook_account_id_foreign FOREIGN KEY (facebook_account_id) REFERENCES public.facebook_accounts(id) ON DELETE CASCADE;


--
-- Name: report_facebook_accounts report_facebook_accounts_report_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.report_facebook_accounts
    ADD CONSTRAINT report_facebook_accounts_report_id_foreign FOREIGN KEY (report_id) REFERENCES public.reports(id) ON DELETE CASCADE;


--
-- Name: task_logs task_logs_automation_task_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.task_logs
    ADD CONSTRAINT task_logs_automation_task_id_foreign FOREIGN KEY (automation_task_id) REFERENCES public.automation_tasks(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict kmBVNicxr4On6HsrjVfRdYSvH1NxEiOv6jIwLOxLe65N4eJ8Z6uNsJAUeazMHej

