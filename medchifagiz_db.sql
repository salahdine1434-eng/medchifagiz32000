-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 06 juil. 2026 à 14:05
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `medchifagiz`
--

-- --------------------------------------------------------

--
-- Structure de la table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `bg` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `user_name` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `activity_type` varchar(30) NOT NULL DEFAULT 'super_admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `icon`, `bg`, `color`, `title`, `description`, `user_name`, `created_at`, `activity_type`) VALUES
(1, 'fa-user-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مسؤول', 'تم تعديل بيانات jiji', 'Super Admin', '2026-06-09 23:18:13', 'super_admin'),
(2, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب qqq', 'Super Admin', '2026-06-09 23:18:17', 'super_admin'),
(3, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب jiji', 'Super Admin', '2026-06-09 23:26:12', 'super_admin'),
(4, 'fa-user-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مسؤول', 'تم تعديل بيانات qqq', 'Super Admin', '2026-06-09 23:26:20', 'super_admin'),
(5, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب nnnmm', 'Super Admin', '2026-06-11 10:03:18', 'super_admin'),
(6, 'fa-lock', 'rgba(239,68,68,.12)', '#f87171', 'تعطيل مسؤول', 'تم تعطيل حساب qqq', 'Super Admin', '2026-06-11 10:03:19', 'super_admin'),
(7, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 10:04:54', 'super_admin'),
(8, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 10:12:36', 'super_admin'),
(9, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 10:13:19', 'super_admin'),
(10, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 10:13:28', 'super_admin'),
(11, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'حفظ إعدادات الصيانة', 'تم تحديث إعدادات وضع الصيانة', 'Super Admin', '2026-06-11 10:26:18', 'super_admin'),
(12, 'fa-comment-medical', 'rgba(245,158,11,.15)', '#fbbf24', 'تعديل رسالة الصيانة', 'تم تحديث رسالة المستخدمين أثناء الصيانة', 'Super Admin', '2026-06-11 10:26:27', 'super_admin'),
(13, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-06-11 10:26:33', 'super_admin'),
(14, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-06-11 10:34:37', 'super_admin'),
(15, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-06-11 10:34:58', 'super_admin'),
(16, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-06-11 10:37:33', 'super_admin'),
(17, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-06-11 10:37:42', 'super_admin'),
(18, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-06-11 10:56:35', 'super_admin'),
(19, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-06-11 10:56:37', 'super_admin'),
(20, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-06-11 10:56:38', 'super_admin'),
(21, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-06-11 10:56:52', 'super_admin'),
(22, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-06-11 11:10:04', 'super_admin'),
(23, 'fa-comment-medical', 'rgba(245,158,11,.15)', '#fbbf24', 'تعديل رسالة الصيانة', 'تم تحديث رسالة المستخدمين أثناء الصيانة', 'Super Admin', '2026-06-11 11:10:46', 'super_admin'),
(24, 'fa-comment-medical', 'rgba(245,158,11,.15)', '#fbbf24', 'تعديل رسالة الصيانة', 'تم تحديث رسالة المستخدمين أثناء الصيانة', 'Super Admin', '2026-06-11 11:10:47', 'super_admin'),
(25, 'fa-comment-medical', 'rgba(245,158,11,.15)', '#fbbf24', 'تعديل رسالة الصيانة', 'تم تحديث رسالة المستخدمين أثناء الصيانة', 'Super Admin', '2026-06-11 11:10:47', 'super_admin'),
(26, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-06-11 11:11:03', 'super_admin'),
(27, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-06-11 11:12:18', 'super_admin'),
(28, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مسؤول', 'تم تفعيل حساب qqq', 'Super Admin', '2026-06-11 11:17:03', 'super_admin'),
(29, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 11:34:38', 'super_admin'),
(30, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 11:38:25', 'super_admin'),
(31, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 11:38:41', 'super_admin'),
(32, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:04:16', 'super_admin'),
(33, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:07:23', 'super_admin'),
(34, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:21:40', 'super_admin'),
(35, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:49', 'super_admin'),
(36, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:51', 'super_admin'),
(37, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:52', 'super_admin'),
(38, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:52', 'super_admin'),
(39, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:53', 'super_admin'),
(40, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:53', 'super_admin'),
(41, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:53', 'super_admin'),
(42, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:53', 'super_admin'),
(43, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:53', 'super_admin'),
(44, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:24:54', 'super_admin'),
(45, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-06-11 12:38:18', 'super_admin'),
(46, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Super Admin', '2026-06-11 14:50:27', 'super_admin'),
(47, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب eee', 'Super Admin', '2026-06-11 16:00:04', 'super_admin'),
(48, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب dddbb', 'Super Admin', '2026-06-11 16:09:58', 'super_admin'),
(49, 'fa-lock', 'rgba(239,68,68,.12)', '#f87171', 'تعطيل مسؤول', 'تم تعطيل حساب qqq', 'Super Admin', '2026-06-11 16:11:01', 'super_admin'),
(50, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مسؤول', 'تم تفعيل حساب qqq', 'Super Admin', '2026-06-11 16:11:14', 'super_admin'),
(51, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مؤسسة', 'تم تعديل بيانات Nadjet Hakem11', 'Super Admin', '2026-06-16 16:21:00', 'super_admin'),
(52, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>neurologie</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-20 18:42:00', 'clinic_admin'),
(53, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>medecin intene</strong> نهائياً', 'Clinic Admin', '2026-06-20 18:42:12', 'clinic_admin'),
(54, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>tromato</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-20 18:57:35', 'clinic_admin'),
(55, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>tromato</strong> نهائياً', 'Clinic Admin', '2026-06-20 18:58:00', 'clinic_admin'),
(56, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>omar</strong> نهائياً ', 'Clinic Admin', '2026-06-20 19:00:00', 'clinic_admin'),
(57, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>jiuy</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-06-20 19:31:06', 'clinic_admin'),
(58, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>moh</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-06-20 19:32:02', 'clinic_admin'),
(59, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>naji</strong> بصلاحية ممرض/ة في مصلحة ', 'Clinic Admin', '2026-06-20 19:33:20', 'clinic_admin'),
(60, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>moh</strong> بصلاحية طبيب في مصلحة neurologie', 'Clinic Admin', '2026-06-21 11:19:25', 'clinic_admin'),
(61, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>medecin intene</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 11:19:54', 'clinic_admin'),
(62, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>cardio</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 11:20:01', 'clinic_admin'),
(63, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>samira</strong> بصلاحية ممرض/ة في مصلحة medecin intene', 'Clinic Admin', '2026-06-21 11:20:50', 'clinic_admin'),
(64, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>dddddd</strong> بصلاحية طبيب في مصلحة neurologie', 'Clinic Admin', '2026-06-21 11:21:36', 'clinic_admin'),
(65, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>tromato</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 11:22:29', 'clinic_admin'),
(66, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>gastro</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 11:22:45', 'clinic_admin'),
(67, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>pedeatre</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 11:22:56', 'clinic_admin'),
(68, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:12:19', 'clinic_admin'),
(69, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:13:27', 'clinic_admin'),
(70, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:13:30', 'clinic_admin'),
(71, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:39:08', 'clinic_admin'),
(72, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:39:15', 'clinic_admin'),
(73, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:46:35', 'clinic_admin'),
(74, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-06-21 12:46:40', 'clinic_admin'),
(75, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>pedeatre</strong> نهائياً', 'Clinic Admin', '2026-06-21 16:08:04', 'clinic_admin'),
(76, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>gastro</strong>', 'Clinic Admin', '2026-06-21 18:39:28', 'clinic_admin'),
(77, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>tromato</strong>', 'Clinic Admin', '2026-06-21 18:39:30', 'clinic_admin'),
(78, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>cardio</strong>', 'Clinic Admin', '2026-06-21 18:44:03', 'clinic_admin'),
(79, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 18:44:08', 'clinic_admin'),
(80, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>chikh</strong> بصلاحية Service Admin في مصلحة medecin intene', 'Clinic Admin', '2026-06-21 18:51:41', 'clinic_admin'),
(81, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>chikh</strong>', 'Clinic Admin', '2026-06-21 18:52:20', 'clinic_admin'),
(82, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>naji</strong> بصلاحية طبيب في مصلحة medecin intene', 'Clinic Admin', '2026-06-21 18:54:06', 'clinic_admin'),
(83, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>gastro</strong>', 'Clinic Admin', '2026-06-21 19:29:29', 'clinic_admin'),
(84, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>medecin intene</strong>', 'Clinic Admin', '2026-06-21 19:29:30', 'clinic_admin'),
(85, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>naji</strong>', 'Clinic Admin', '2026-06-21 19:29:51', 'clinic_admin'),
(86, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>tromato</strong>', 'Clinic Admin', '2026-06-21 19:30:23', 'clinic_admin'),
(87, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>gastro</strong>', 'Clinic Admin', '2026-06-21 19:30:24', 'clinic_admin'),
(88, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>tromato</strong>', 'Clinic Admin', '2026-06-21 19:30:25', 'clinic_admin'),
(89, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>moh</strong>', 'Clinic Admin', '2026-06-21 19:30:48', 'clinic_admin'),
(90, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>chikh</strong>', 'Clinic Admin', '2026-06-21 19:32:59', 'clinic_admin'),
(91, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>naji</strong>', 'Clinic Admin', '2026-06-21 19:33:00', 'clinic_admin'),
(92, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>chikh</strong>', 'Clinic Admin', '2026-06-21 19:34:50', 'clinic_admin'),
(93, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>chikh</strong>', 'Clinic Admin', '2026-06-21 19:35:06', 'clinic_admin'),
(94, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 19:35:07', 'clinic_admin'),
(95, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>samira</strong>', 'Clinic Admin', '2026-06-21 19:35:08', 'clinic_admin'),
(96, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>moh</strong>', 'Clinic Admin', '2026-06-21 19:35:10', 'clinic_admin'),
(97, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>gastro</strong>', 'Clinic Admin', '2026-06-21 19:35:31', 'clinic_admin'),
(98, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>nnn</strong> بصلاحية طبيب في مصلحة neurologie', 'Clinic Admin', '2026-06-21 20:41:02', 'clinic_admin'),
(99, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>medecin intene</strong>', 'Clinic Admin', '2026-06-21 20:41:18', 'clinic_admin'),
(100, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>cardio</strong>', 'Clinic Admin', '2026-06-21 20:41:19', 'clinic_admin'),
(101, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>tromato</strong>', 'Clinic Admin', '2026-06-21 20:41:20', 'clinic_admin'),
(102, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>hke,,m</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 20:42:44', 'clinic_admin'),
(103, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>mjjyy</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 20:52:15', 'clinic_admin'),
(104, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>mi</strong> بصلاحية طبيب في مصلحة neurologie', 'Clinic Admin', '2026-06-21 21:05:20', 'clinic_admin'),
(105, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>opi</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 21:05:39', 'clinic_admin'),
(106, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>dddddd</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-06-21 21:06:26', 'clinic_admin'),
(107, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>nnn</strong> بصلاحية Service Admin في مصلحة ', 'Clinic Admin', '2026-06-21 21:07:40', 'clinic_admin'),
(108, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>mino</strong> —  2 غرفة / 2 سرير', 'Clinic Admin', '2026-06-21 21:08:04', 'clinic_admin'),
(109, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>hakem</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 21:10:58', 'clinic_admin'),
(110, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>hakem</strong> نهائياً', 'Clinic Admin', '2026-06-21 21:12:41', 'clinic_admin'),
(111, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>nnn</strong> نهائياً ', 'Clinic Admin', '2026-06-21 21:12:54', 'clinic_admin'),
(112, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>dddddd</strong> نهائياً ', 'Clinic Admin', '2026-06-21 21:15:14', 'clinic_admin'),
(113, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>chikh</strong> نهائياً ', 'Clinic Admin', '2026-06-21 21:15:24', 'clinic_admin'),
(114, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>mino</strong> نهائياً', 'Clinic Admin', '2026-06-21 21:17:14', 'clinic_admin'),
(115, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>opi</strong>', 'Clinic Admin', '2026-06-21 21:18:04', 'clinic_admin'),
(116, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>mi</strong>', 'Clinic Admin', '2026-06-21 21:21:22', 'clinic_admin'),
(117, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>nnn</strong>', 'Clinic Admin', '2026-06-21 21:21:23', 'clinic_admin'),
(118, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>nnn</strong>', 'Clinic Admin', '2026-06-21 21:21:34', 'clinic_admin'),
(119, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>mi</strong>', 'Clinic Admin', '2026-06-21 21:21:35', 'clinic_admin'),
(120, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>mjjyy</strong>', 'Clinic Admin', '2026-06-21 21:23:23', 'clinic_admin'),
(121, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>mjjyy</strong>', 'Clinic Admin', '2026-06-21 21:23:31', 'clinic_admin'),
(122, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>opi</strong>', 'Clinic Admin', '2026-06-21 21:23:32', 'clinic_admin'),
(123, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>dddddd</strong> بصلاحية Service Admin في مصلحة neurologie', 'Clinic Admin', '2026-06-21 21:23:58', 'clinic_admin'),
(124, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 21:24:04', 'clinic_admin'),
(125, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 21:24:09', 'clinic_admin'),
(126, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>miko</strong> —  3 غرفة / 5 سرير', 'Clinic Admin', '2026-06-21 21:24:36', 'clinic_admin'),
(127, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>opi</strong> نهائياً', 'Clinic Admin', '2026-06-21 21:24:47', 'clinic_admin'),
(128, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>mkiopl</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-06-21 21:27:05', 'clinic_admin'),
(129, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>mi</strong>', 'Clinic Admin', '2026-06-21 21:30:53', 'clinic_admin'),
(130, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>samira</strong>', 'Clinic Admin', '2026-06-21 21:31:12', 'clinic_admin'),
(131, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>samira</strong>', 'Clinic Admin', '2026-06-21 21:31:14', 'clinic_admin'),
(132, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>mi</strong>', 'Clinic Admin', '2026-06-21 21:31:18', 'clinic_admin'),
(133, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>mkiopl</strong>', 'Clinic Admin', '2026-06-21 21:33:54', 'clinic_admin'),
(134, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>mkiopl</strong>', 'Clinic Admin', '2026-06-21 21:33:59', 'clinic_admin'),
(135, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 21:34:11', 'clinic_admin'),
(136, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>mkiopl</strong>', 'Clinic Admin', '2026-06-21 21:34:12', 'clinic_admin'),
(137, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>mkiopl</strong>', 'Clinic Admin', '2026-06-21 21:34:15', 'clinic_admin'),
(138, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>dddddd</strong>', 'Clinic Admin', '2026-06-21 21:34:16', 'clinic_admin'),
(139, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>mkiopl</strong> نهائياً ', 'Clinic Admin', '2026-06-21 21:35:53', 'clinic_admin'),
(140, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>medecin intenellll</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-06-21 21:37:07', 'clinic_admin'),
(141, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>nn</strong> بصلاحية Service Admin في مصلحة miko', 'Clinic Admin', '2026-06-21 21:37:46', 'clinic_admin'),
(142, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>miko</strong> نهائياً', 'Clinic Admin', '2026-06-21 21:38:42', 'clinic_admin'),
(143, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>medecin intenellll</strong>', 'Clinic Admin', '2026-06-21 21:39:38', 'clinic_admin'),
(144, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>mjjyy</strong>', 'Clinic Admin', '2026-06-21 21:39:39', 'clinic_admin'),
(145, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>loiuyt</strong> بصلاحية طبيب في مصلحة hke,,m', 'Clinic Admin', '2026-06-21 21:40:13', 'clinic_admin'),
(146, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>loiuyt</strong>', 'Clinic Admin', '2026-06-21 21:40:59', 'clinic_admin'),
(147, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>nn</strong>', 'Clinic Admin', '2026-06-21 21:41:00', 'clinic_admin'),
(148, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>niii</strong> بصلاحية Service Admin في مصلحة cardio', 'Clinic Admin', '2026-06-21 21:42:17', 'clinic_admin'),
(149, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>iuyy</strong> بصلاحية طبيب في مصلحة neurologie', 'Clinic Admin', '2026-06-21 21:43:48', 'clinic_admin'),
(150, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>loiuyt</strong>', 'Clinic Admin', '2026-06-22 00:04:24', 'clinic_admin'),
(151, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>nn</strong>', 'Clinic Admin', '2026-06-22 00:04:25', 'clinic_admin'),
(152, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>iuyy</strong>', 'Clinic Admin', '2026-06-22 00:04:44', 'clinic_admin'),
(153, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>iuyy</strong>', 'Clinic Admin', '2026-06-22 00:04:49', 'clinic_admin'),
(154, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>nadjet</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 14:45:13', 'clinic_admin'),
(155, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مستخدم', 'تم تعديل بيانات <strong>nadjet</strong>', 'Clinic Admin', '2026-07-05 14:47:36', 'clinic_admin'),
(156, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>najet</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 14:49:17', 'clinic_admin'),
(157, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>nnn</strong> بصلاحية ممرض/ة في مصلحة ', 'Clinic Admin', '2026-07-05 14:52:33', 'clinic_admin'),
(158, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>loj</strong> —  0 غرفة / 0 سرير', 'Clinic Admin', '2026-07-05 14:58:43', 'clinic_admin'),
(159, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>moh</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:11:48', 'clinic_admin'),
(160, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>nnn</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:14', 'clinic_admin'),
(161, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>najet</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:17', 'clinic_admin'),
(162, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>nadjet</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:19', 'clinic_admin'),
(163, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>iuyy</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:22', 'clinic_admin'),
(164, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>niii</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:24', 'clinic_admin'),
(165, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>loiuyt</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:26', 'clinic_admin'),
(166, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>nn</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:29', 'clinic_admin'),
(167, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>dddddd</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:33', 'clinic_admin'),
(168, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>mi</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:36', 'clinic_admin'),
(169, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>nnn</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:53', 'clinic_admin'),
(170, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>naji</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:56', 'clinic_admin'),
(171, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>dddddd</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:12:58', 'clinic_admin'),
(172, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>samira</strong> نهائياً ', 'Clinic Admin', '2026-07-05 15:13:01', 'clinic_admin'),
(173, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>najit</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 15:33:00', 'clinic_admin'),
(174, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>mohammed</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 17:33:20', 'clinic_admin'),
(175, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>info</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 17:37:29', 'clinic_admin'),
(176, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مؤسسة', 'تم تعديل بيانات nadjety123', 'Super Admin', '2026-07-05 18:00:48', 'super_admin'),
(177, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مؤسسة', 'تم تعديل بيانات nadjety', 'Super Admin', '2026-07-05 18:10:31', 'super_admin'),
(178, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مؤسسة', 'تم تعديل بيانات khyrora', 'Super Admin', '2026-07-05 18:11:06', 'super_admin'),
(179, 'fa-user-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مسؤول', 'تم تعديل بيانات khyrora', 'Super Admin', '2026-07-05 18:12:04', 'super_admin'),
(180, 'fa-user-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مسؤول', 'تم تعديل بيانات khyroraty', 'Super Admin', '2026-07-05 18:13:17', 'super_admin'),
(181, 'fa-lock', 'rgba(239,68,68,.12)', '#f87171', 'تعطيل مسؤول', 'تم تعطيل حساب jndu', 'Super Admin', '2026-07-05 18:22:45', 'super_admin'),
(182, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مسؤول', 'تم تفعيل حساب jndu', 'Super Admin', '2026-07-05 18:24:13', 'super_admin'),
(183, 'fa-lock', 'rgba(239,68,68,.12)', '#f87171', 'تعطيل مسؤول', 'تم تعطيل حساب jndu', 'Admin', '2026-07-05 18:24:19', 'super_admin'),
(184, 'fa-user-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مسؤول', 'تم تعديل بيانات khyroraty', 'Admin', '2026-07-05 18:27:19', 'super_admin'),
(185, 'fa-lock', 'rgba(239,68,68,.12)', '#f87171', 'تعطيل مسؤول', 'تم تعطيل حساب khyroraty', 'Admin', '2026-07-05 18:27:46', 'super_admin'),
(186, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مسؤول', 'تم تفعيل حساب khyroraty', 'Admin', '2026-07-05 18:28:04', 'super_admin'),
(187, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب qqq', 'Admin', '2026-07-05 18:28:21', 'super_admin'),
(188, 'fa-trash', 'rgba(239,68,68,.12)', '#f87171', 'حذف مسؤول', 'تم حذف حساب qqq', 'Moderator', '2026-07-05 18:28:43', 'super_admin'),
(189, 'fa-image', 'rgba(14,165,233,.12)', '#38bdf8', 'تحديث الشعار', 'تم رفع شعار جديد للمنصة', 'Moderator', '2026-07-05 18:30:28', 'super_admin'),
(190, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Moderator', '2026-07-05 18:30:32', 'super_admin'),
(191, 'fa-gear', 'rgba(14,165,233,.12)', '#38bdf8', 'حفظ إعدادات المنصة', 'تم تحديث البيانات الأساسية للمنصة', 'Super Admin', '2026-07-05 18:31:38', 'super_admin'),
(192, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'حفظ إعدادات الصيانة', 'تم تحديث إعدادات وضع الصيانة', 'Super Admin', '2026-07-05 18:34:58', 'super_admin'),
(193, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:35:00', 'super_admin'),
(194, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-07-05 18:35:20', 'super_admin'),
(195, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:35:30', 'super_admin'),
(196, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:37:06', 'super_admin'),
(197, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-07-05 18:37:32', 'super_admin'),
(198, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:37:46', 'super_admin'),
(199, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-07-05 18:38:00', 'super_admin'),
(200, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:38:09', 'super_admin'),
(201, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-07-05 18:39:07', 'super_admin'),
(202, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'حفظ إعدادات الصيانة', 'تم تحديث إعدادات وضع الصيانة', 'Super Admin', '2026-07-05 18:40:00', 'super_admin'),
(203, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:40:01', 'super_admin'),
(204, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-07-05 18:40:06', 'super_admin'),
(205, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-07-05 18:40:38', 'super_admin'),
(206, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'حفظ إعدادات الصيانة', 'تم تحديث إعدادات وضع الصيانة', 'Super Admin', '2026-07-05 18:41:43', 'super_admin'),
(207, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:41:44', 'super_admin'),
(208, 'fa-users-gear', 'rgba(167,139,250,.15)', '#a78bfa', 'تعديل صلاحيات الوصول', 'تم تحديث قائمة المستخدمين المسموح لهم أثناء الصيانة', 'Super Admin', '2026-07-05 18:41:49', 'super_admin'),
(209, 'fa-screwdriver-wrench', 'rgba(239,68,68,.12)', '#f87171', 'تفعيل وضع الصيانة', 'المنصة الآن في وضع الصيانة', 'Super Admin', '2026-07-05 18:46:06', 'super_admin'),
(210, 'fa-check-circle', 'rgba(16,185,129,.15)', '#34d399', 'إيقاف وضع الصيانة', 'المنصة تعمل بشكل طبيعي', 'Super Admin', '2026-07-05 18:46:15', 'super_admin'),
(211, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>hakemdjefal</strong> —  5 غرفة / 10 سرير', 'Clinic Admin', '2026-07-05 18:48:59', 'clinic_admin'),
(212, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>irjence</strong> —  11 غرفة / 61 سرير', 'Clinic Admin', '2026-07-05 18:51:53', 'clinic_admin'),
(213, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>irjence</strong>', 'Clinic Admin', '2026-07-05 18:52:31', 'clinic_admin'),
(214, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>irjence</strong>', 'Clinic Admin', '2026-07-05 18:52:39', 'clinic_admin'),
(215, 'fa-toggle-off', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل مصلحة', 'تم تعطيل مصلحة <strong>irjence</strong>', 'Clinic Admin', '2026-07-05 18:52:44', 'clinic_admin'),
(216, 'fa-toggle-on', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل مصلحة', 'تم تفعيل مصلحة <strong>irjence</strong>', 'Clinic Admin', '2026-07-05 18:53:06', 'clinic_admin'),
(217, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مصلحة', 'تم تعديل بيانات مصلحة <strong>irjencegjjjjjjj</strong>', 'Clinic Admin', '2026-07-05 18:53:37', 'clinic_admin'),
(218, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مصلحة', 'تم حذف مصلحة <strong>irjencegjjjjjjj</strong> نهائياً', 'Clinic Admin', '2026-07-05 18:55:06', 'clinic_admin'),
(219, 'fa-sitemap', 'rgba(16,185,129,.15)', '#34d399', 'إنشاء مصلحة جديدة', ' تم إنشاء مصلحة <strong>gty</strong> — بدون غرف إقامة', 'Clinic Admin', '2026-07-05 18:55:47', 'clinic_admin'),
(220, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>khbbbb</strong> بصلاحية طبيب في مصلحة hakemdjefal', 'Clinic Admin', '2026-07-05 18:58:18', 'clinic_admin'),
(221, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>bhyy</strong> بصلاحية Service Admin في مصلحة gty', 'Clinic Admin', '2026-07-05 18:59:29', 'clinic_admin'),
(222, 'fa-pen', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل مستخدم', 'تم تعديل بيانات <strong>bhyy65</strong>', 'Clinic Admin', '2026-07-05 19:00:31', 'clinic_admin'),
(223, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>khbbbb</strong>', 'Clinic Admin', '2026-07-05 19:01:05', 'clinic_admin'),
(224, 'fa-trash', 'rgba(239,68,68,.15)', '#f87171', 'حذف مستخدم', ' تم حذف حساب <strong>khbbbb</strong> نهائياً ', 'Clinic Admin', '2026-07-05 19:01:20', 'clinic_admin'),
(225, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>serine</strong> بصلاحية ممرض/ة في مصلحة ', 'Clinic Admin', '2026-07-05 19:02:57', 'clinic_admin'),
(226, 'fa-lock', 'rgba(239,68,68,.15)', '#f87171', 'تعطيل حساب', ' تم تعطيل حساب <strong>serine</strong>', 'Clinic Admin', '2026-07-05 19:03:02', 'clinic_admin'),
(227, 'fa-lock-open', 'rgba(16,185,129,.15)', '#34d399', 'تفعيل حساب', ' تم تفعيل حساب <strong>serine</strong>', 'Clinic Admin', '2026-07-05 19:03:20', 'clinic_admin'),
(228, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-07-05 19:05:26', 'clinic_admin'),
(229, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-07-05 19:06:52', 'clinic_admin'),
(230, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-07-05 19:07:01', 'clinic_admin'),
(231, 'fa-gear', 'rgba(14,165,233,.15)', '#38bdf8', 'تعديل إعدادات العيادة', 'تم تحديث بيانات وإعدادات العيادة', 'Clinic Admin', '2026-07-05 19:07:04', 'clinic_admin'),
(232, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>tppaw</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 22:54:54', 'clinic_admin'),
(233, 'fa-user-plus', 'rgba(99,102,241,.15)', '#a5b4fc', 'إضافة مستخدم جديد', ' تم إضافة <strong>jiji</strong> بصلاحية طبيب في مصلحة ', 'Clinic Admin', '2026-07-05 22:55:36', 'clinic_admin');

-- --------------------------------------------------------

--
-- Structure de la table `ai_file_organization`
--

CREATE TABLE `ai_file_organization` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `specialty_ar` varchar(150) DEFAULT NULL,
  `disease_category` varchar(150) DEFAULT NULL,
  `priority` enum('high','medium','low') NOT NULL DEFAULT 'medium',
  `keywords` text DEFAULT NULL,
  `summary` mediumtext DEFAULT NULL,
  `followup_required` tinyint(1) NOT NULL DEFAULT 0,
  `followup` text DEFAULT NULL,
  `missing_info` text DEFAULT NULL,
  `is_incomplete` tinyint(1) NOT NULL DEFAULT 0,
  `suggested_path` varchar(255) DEFAULT NULL,
  `raw_json` mediumtext DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `content_hash` char(64) DEFAULT NULL,
  `record_updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('ok','error') NOT NULL DEFAULT 'ok',
  `error_message` varchar(255) DEFAULT NULL,
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_file_organization`
--

INSERT INTO `ai_file_organization` (`id`, `medical_record_id`, `doctor_id`, `patient_name`, `specialty`, `specialty_ar`, `disease_category`, `priority`, `keywords`, `summary`, `followup_required`, `followup`, `missing_info`, `is_incomplete`, `suggested_path`, `raw_json`, `model`, `content_hash`, `record_updated_at`, `status`, `error_message`, `analyzed_at`, `created_at`, `updated_at`) VALUES
(1, 63, 50, 'rahaf', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"فحص روتيني\",\"طب عام\"]', 'المرضى جاءت لفحص روتيني. لا توجد معلومات كافية عن الحالة. المريضة أنثى.', 0, NULL, '[\"تشخيص\",\"أعراض\",\"تاريخ مرضي\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص روتيني\", \"طب عام\"],\n  \"summary\": \"المرضى جاءت لفحص روتيني. لا توجد معلومات كافية عن الحالة. المريضة أنثى.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تشخيص\", \"أعراض\", \"تاريخ مرضي\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'c88f4d67570c55051ca435d5bf132ce1e33c37dc3f8c8c3ae635a6e87f4486c6', '2026-06-27 16:12:48', 'ok', NULL, '2026-06-28 19:56:03', '2026-06-28 19:56:03', '2026-06-28 19:56:03'),
(2, 62, 50, 'robin', 'Allergy', 'أمراض الحساسية', 'Allergic Reaction', 'low', '[\"حساسية\",\"hay\"]', 'مريضة أنثى تعاني من حساسية، وقد خضعت لبعض التحاليل الطبية والأشعة. تمت إعطاؤها وصفة طبية. لا يوجد موعد قادم للمتابعة.', 0, NULL, '[]', 0, 'Archive / Allergy / Low Priority', '```json\n{\n  \"specialty\": \"Allergy\",\n  \"specialty_ar\": \"أمراض الحساسية\",\n  \"disease_category\": \"Allergic Reaction\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"حساسية\", \"hay\"],\n  \"summary\": \"مريضة أنثى تعاني من حساسية، وقد خضعت لبعض التحاليل الطبية والأشعة. تمت إعطاؤها وصفة طبية. لا يوجد موعد قادم للمتابعة.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [],\n  \"suggested_path\": [\"Archive\", \"Allergy\", \"Low Priority\"]\n}\n```', 'llama-3.3-70b-versatile', 'caa19c9bc8796730ae4b188246e7642cda704f9438e99068e29564de87e58850', '2026-06-26 19:36:53', 'ok', NULL, '2026-06-28 19:56:05', '2026-06-28 19:56:05', '2026-06-28 19:56:05'),
(3, 61, 50, 'chikh', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"فحص\",\"تحاليل\",\"أشعة\"]', 'تم إجراء فحص طبي، وتم إجراء تحاليل طبية، وتم إجراء أشعة. تم كتابة وصفة طبية. المريض ذكر.', 0, NULL, '[\"تشخيص\",\"علاج\",\"تاريخ المريض\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"تحاليل\", \"أشعة\"],\n  \"summary\": \"تم إجراء فحص طبي، وتم إجراء تحاليل طبية، وتم إجراء أشعة. تم كتابة وصفة طبية. المريض ذكر.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تشخيص\", \"علاج\", \"تاريخ المريض\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'bf1e6583a0b6af298a69d8b130f26ce9d09e9b7829a7a91e80a171708301076a', '2026-06-26 19:52:22', 'ok', NULL, '2026-06-28 19:56:06', '2026-06-28 19:56:06', '2026-06-28 19:56:06'),
(4, 60, 50, 'مريض غير محدّد', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'لم يتم تقديم أي معلومات طبية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لم يتم تقديم أي معلومات طبية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 17:11:35', 'ok', NULL, '2026-06-28 19:56:08', '2026-06-28 19:56:08', '2026-06-28 19:56:08'),
(5, 59, 50, 'مريض غير محدّد', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"متابعة\",\"موعد\"]', 'لم يتم تقديم أي معلومات طبية محددة. المريض قد يحتاج إلى متابعة روتينية.', 0, NULL, '[\"تشخيص\",\"علاج\",\"توصيات\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"متابعة\", \"موعد\"],\n  \"summary\": \"لم يتم تقديم أي معلومات طبية محددة. المريض قد يحتاج إلى متابعة روتينية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تشخيص\", \"علاج\", \"توصيات\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 12:59:06', 'ok', NULL, '2026-06-28 19:56:09', '2026-06-28 19:56:09', '2026-06-28 19:56:09'),
(6, 58, 50, 'مريض غير محدّد', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'لا توجد معلومات كافية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا توجد معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 12:56:12', 'ok', NULL, '2026-06-28 19:56:11', '2026-06-28 19:56:11', '2026-06-28 19:56:11'),
(7, 57, 50, 'مريض غير محدّد', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'لا توجد معلومات كافية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا توجد معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 12:43:27', 'ok', NULL, '2026-06-28 19:56:23', '2026-06-28 19:56:12', '2026-06-28 19:56:23'),
(8, 56, 50, 'مريض غير محدّد', 'Unknown', 'مجهول', 'Unknown', 'low', '[]', 'لا توجد معلومات كافية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"مجهول\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا توجد معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 11:56:29', 'ok', NULL, '2026-06-28 19:56:13', '2026-06-28 19:56:13', '2026-06-28 19:56:13'),
(10, 55, 50, 'مريض غير محدّد', 'Unknown', 'مجهول', 'Unknown', 'low', '[]', 'لا توجد معلومات كافية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"مجهول\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا توجد معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-26 11:54:02', 'ok', NULL, '2026-06-28 19:56:19', '2026-06-28 19:56:19', '2026-06-28 19:56:19'),
(11, 54, 50, 'مريض غير محدّد', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'لم يتم تقديم أي معلومات طبية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لم يتم تقديم أي معلومات طبية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-25 18:55:07', 'ok', NULL, '2026-06-28 19:56:20', '2026-06-28 19:56:20', '2026-06-28 19:56:20'),
(12, 53, 50, 'مريض غير محدّد', 'Unknown', 'مجهول', 'Unknown', 'low', '[]', 'لم يتم تقديم أي معلومات طبية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"مجهول\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لم يتم تقديم أي معلومات طبية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-25 18:51:37', 'ok', NULL, '2026-06-28 19:56:25', '2026-06-28 19:56:21', '2026-06-28 19:56:25'),
(15, 52, 50, 'مريض غير محدّد', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"متابعة\",\"موعد\"]', 'الملف يحتوي على معلومات قليلة، ولا يوجد تشخيص واضح. لا توجد تفاصيل كافية لتحديد حالة المريض.', 0, NULL, '[\"تاريخ المريض\",\"أعراض\",\"نتائج الفحوصات\",\"تشخيص\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"متابعة\", \"موعد\"],\n  \"summary\": \"الملف يحتوي على معلومات قليلة، ولا يوجد تشخيص واضح. لا توجد تفاصيل كافية لتحديد حالة المريض.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تاريخ المريض\", \"أعراض\", \"نتائج الفحوصات\", \"تشخيص\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-25 15:06:20', 'ok', NULL, '2026-06-28 19:56:52', '2026-06-28 19:56:26', '2026-06-28 19:56:52'),
(16, 51, 50, 'مريض غير محدّد', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'لا توجد معلومات كافية.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا توجد معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-25 15:06:04', 'ok', NULL, '2026-06-28 19:56:28', '2026-06-28 19:56:28', '2026-06-28 19:56:28'),
(18, 50, 50, 'مريض غير محدّد', 'Unknown', 'مجهول', 'Unknown', 'low', '[]', 'لا يوجد ملخص طبي متاح.', 0, NULL, '[\"جميع المعلومات\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"مجهول\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"لا يوجد ملخص طبي متاح.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"جميع المعلومات\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '2f9630620b296dbf81698aa4307d8890eccd9483a676900a56d3805787f03dab', '2026-06-25 14:39:44', 'ok', NULL, '2026-06-28 19:56:46', '2026-06-28 19:56:46', '2026-06-28 19:56:46'),
(19, 49, 50, 'hayk', 'Unknown', 'غير محدد', 'Unknown', 'low', '[]', 'الملف الطبي غير مكتمل، لا معلومات كافية.', 0, NULL, '[\"سبب الفحص\",\"تاريخ المتابعة\",\"تشخيص\",\"علاج\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [],\n  \"summary\": \"الملف الطبي غير مكتمل، لا معلومات كافية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"سبب الفحص\", \"تاريخ المتابعة\", \"تشخيص\", \"علاج\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'e56d6c5d94f7bd6e0a5079783ac9cbc4e641af5dcedad4aa771b32866d88f032', '2026-06-27 19:38:48', 'ok', NULL, '2026-06-28 19:56:49', '2026-06-28 19:56:49', '2026-06-28 19:56:49'),
(20, 48, 50, 'lilo', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"موعد\"]', 'المرضى أنثى، بدون معلومات إضافية. لا يوجد تشخيص واضح.', 0, NULL, '[\"تاريخ المريض\",\"نتائج الفحوصات\",\"تشخيص المرض\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"موعد\"],\n  \"summary\": \"المرضى أنثى، بدون معلومات إضافية. لا يوجد تشخيص واضح.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تاريخ المريض\", \"نتائج الفحوصات\", \"تشخيص المرض\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '96df5a771144fd6efa6e23091cbb7f5c09e746760d213281383099581ba12819', '2026-06-25 11:50:36', 'ok', NULL, '2026-06-28 19:56:51', '2026-06-28 19:56:51', '2026-06-28 19:56:51'),
(22, 47, 50, 'mahmoud', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"تحاليل\",\"أشعة\"]', 'الملف يحتوي على معلومات غير واضحة حول فحص و تحاليل و أشعة. لا توجد معلومات كافية لتحديد الحالة المرضية.', 0, NULL, '[\"تشخيص\",\"علاج\",\"تاريخ مرضي\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"تحاليل\", \"أشعة\"],\n  \"summary\": \"الملف يحتوي على معلومات غير واضحة حول فحص و تحاليل و أشعة. لا توجد معلومات كافية لتحديد الحالة المرضية.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تشخيص\", \"علاج\", \"تاريخ مرضي\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'ee913d8125bfb183112f4e1d5efc1dc4e61ab855d7d140bbf14e5e830e1b383d', '2026-06-25 11:48:18', 'ok', NULL, '2026-06-28 19:56:54', '2026-06-28 19:56:54', '2026-06-28 19:56:54'),
(23, 46, 50, 'saffae', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"PARACETAMOL\",\"NFS\",\"CRP\",\"RX\"]', 'المرضى أنثى، خضعت لتحاليل طبية و أشعة، وتم وصف دواء PARACETAMOL. لا توجد معلومات كافية لتحديد الحالة المرضية بدقة.', 0, NULL, '[\"تشخيص دقيق\",\"أعراض المريض\",\"تاريخ مرضي\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"PARACETAMOL\", \"NFS\", \"CRP\", \"RX\"],\n  \"summary\": \"المرضى أنثى، خضعت لتحاليل طبية و أشعة، وتم وصف دواء PARACETAMOL. لا توجد معلومات كافية لتحديد الحالة المرضية بدقة.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"تشخيص دقيق\", \"أعراض المريض\", \"تاريخ مرضي\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'e9fa52ddaec97e1af3124338e979a327b832c64ca49c6e2697abb1f5256ac2a9', '2026-06-25 11:31:35', 'ok', NULL, '2026-06-28 19:56:55', '2026-06-28 19:56:55', '2026-06-28 19:56:55'),
(24, 45, 50, 'lamia', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"crp\",\"rx\",\"مجهول\"]', 'المرضى أنثى، أجريت لها تحاليل طبية تشمل CRP، وأشعة RX. لم يتم تحديد تشخيص محدد.', 0, NULL, '[\"التشخيص\",\"التاريخ الطبي\",\"أعراض المريض\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"crp\", \"rx\", \"مجهول\"],\n  \"summary\": \"المرضى أنثى، أجريت لها تحاليل طبية تشمل CRP، وأشعة RX. لم يتم تحديد تشخيص محدد.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"التشخيص\", \"التاريخ الطبي\", \"أعراض المريض\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '76e0395fd369809f0c4520b4a0449ac48a991401fa6ddb592bd118d73c968cb1', '2026-06-25 11:10:08', 'ok', NULL, '2026-06-28 19:56:57', '2026-06-28 19:56:57', '2026-06-28 19:56:57'),
(25, 44, 50, 'najet', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"paracetamol\",\"heptajil\",\"doliprane\"]', 'المرضى أنثى، وقد تم وصف دواء paracetamol و heptajil و doliprane. لا توجد معلومات كافية لتحديد فئة المرض.', 0, NULL, '[\"فئة المرض\",\"تشخيص المرض\",\"أعراض المرض\"]', 1, 'Archive / General Practice / Low Priority', '```json\n{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"paracetamol\", \"heptajil\", \"doliprane\"],\n  \"summary\": \"المرضى أنثى، وقد تم وصف دواء paracetamol و heptajil و doliprane. لا توجد معلومات كافية لتحديد فئة المرض.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"فئة المرض\", \"تشخيص المرض\", \"أعراض المرض\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}\n```', 'llama-3.3-70b-versatile', 'c7f5b3d1710da9e8c9a3a81b7efb87fe00906789907d19be1254f623c987189d', '2026-06-24 21:03:34', 'ok', NULL, '2026-06-28 19:56:58', '2026-06-28 19:56:58', '2026-06-28 19:56:58'),
(26, 43, 50, 'najet', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"NFS\",\"فحص\"]', 'المرضى أنثى، أُجري فحص طبي. النتيجة: NFS. لا يوجد موعد قادم.', 0, NULL, '[\"نتائج التحاليل\",\"تشخيص\",\"علاج\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"NFS\", \"فحص\"],\n  \"summary\": \"المرضى أنثى، أُجري فحص طبي. النتيجة: NFS. لا يوجد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"نتائج التحاليل\", \"تشخيص\", \"علاج\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '7349ae09532272dd96645fe0edf639c6e919e427b72ad951100e9b6519684f77', '2026-06-24 19:44:00', 'ok', NULL, '2026-06-28 19:57:00', '2026-06-28 19:57:00', '2026-06-28 19:57:00'),
(27, 42, 50, 'Bachir', 'Orthopedic', 'عظام', 'Joint Injury', 'high', '[\"ألم مفصل\",\"تورم\",\"صعوبة الحركة\"]', 'مريض يعاني من ألم حاد في الركبة بعد سقوط، مصحوب بتورم وصعوبة في الحركة. يتم طلب التصوير لتقييم الإصابة أو وجود كسر. المريض لديه تاريخ من أمراض مزمنة تشمل السكري والضغط.', 1, 'موعد قادم', '[]', 0, 'Archive / Orthopedic / High Priority', '```json\n{\n  \"specialty\": \"Orthopedic\",\n  \"specialty_ar\": \"عظام\",\n  \"disease_category\": \"Joint Injury\",\n  \"priority\": \"High\",\n  \"keywords\": [\"ألم مفصل\", \"تورم\", \"صعوبة الحركة\"],\n  \"summary\": \"مريض يعاني من ألم حاد في الركبة بعد سقوط، مصحوب بتورم وصعوبة في الحركة. يتم طلب التصوير لتقييم الإصابة أو وجود كسر. المريض لديه تاريخ من أمراض مزمنة تشمل السكري والضغط.\",\n  \"followup_required\": true,\n  \"followup\": \"موعد قادم\",\n  \"missing_info\": [],\n  \"suggested_path\": [\"Archive\", \"Orthopedic\", \"High Priority\"]\n}\n```', 'llama-3.3-70b-versatile', 'b925f41813b4e82c5c7a29169e4a893d98b52bd4e3f69c5290d8256203e9005e', '2026-06-01 20:44:41', 'ok', NULL, '2026-06-28 19:57:01', '2026-06-28 19:57:01', '2026-06-28 19:57:01'),
(28, 33, 50, 'حليمة', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"موعد\"]', 'المرضى أنثى، سبب الفحص غير واضح. لم يتم تحديد موعد قادم.', 0, NULL, '[\"سبب الفحص\",\"تشخيص\",\"علاج\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"موعد\"],\n  \"summary\": \"المرضى أنثى، سبب الفحص غير واضح. لم يتم تحديد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"سبب الفحص\", \"تشخيص\", \"علاج\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'd24ff014b46bdf7b550b7352a12389d0712bbfdfdae6d0dc92066b059a781216', '2026-05-29 20:44:28', 'ok', NULL, '2026-06-28 19:57:04', '2026-06-28 19:57:04', '2026-06-28 19:57:04'),
(29, 70, 50, 'oiy', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"موعد\"]', 'تم إجراء فحص طبي، ولا يوجد موعد قادم.', 0, NULL, '[\"نتائج الفحص\",\"تشخيص المرض\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"موعد\"],\n  \"summary\": \"تم إجراء فحص طبي، ولا يوجد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"نتائج الفحص\", \"تشخيص المرض\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '41542d545141abe7494063e3edd4bbcb10af05f9656519c38ec95cfffdd27a80', '2026-07-05 23:00:36', 'ok', NULL, '2026-07-06 11:55:40', '2026-07-06 11:55:40', '2026-07-06 11:55:40'),
(30, 69, 50, 'inomina', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"موعد\"]', 'المرضى أنثى، سبب الفحص غير واضح. لا يوجد موعد قادم.', 0, NULL, '[\"سبب الفحص\",\"تشخيص\",\"علاج\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"موعد\"],\n  \"summary\": \"المرضى أنثى، سبب الفحص غير واضح. لا يوجد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"سبب الفحص\", \"تشخيص\", \"علاج\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', '5e62f179ab71bb9a1122c9a5e9d37c5e448472089f3510c1b5ec03feecbf99b4', '2026-07-01 20:51:14', 'ok', NULL, '2026-07-06 11:55:42', '2026-07-06 11:55:42', '2026-07-06 11:55:42'),
(31, 68, 50, 'hakemnadjet', 'General Practice', 'طب عام', 'Unknown', 'low', '[\"فحص روتيني\",\"طب عام\"]', 'المرضى أنثى، قامت بزيارة الفحص الروتيني. لم يتم ذكر أي أعراض أو مشاكل صحية محددة.', 0, NULL, '[\"نتائج الفحص\",\"تاريخ المريض الطبي\"]', 1, 'Archive / General Practice / Low Priority', '{\n  \"specialty\": \"General Practice\",\n  \"specialty_ar\": \"طب عام\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص روتيني\", \"طب عام\"],\n  \"summary\": \"المرضى أنثى، قامت بزيارة الفحص الروتيني. لم يتم ذكر أي أعراض أو مشاكل صحية محددة.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"نتائج الفحص\", \"تاريخ المريض الطبي\"],\n  \"suggested_path\": [\"Archive\", \"General Practice\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'e2a93d2c6994914aef187c27452575f7e069e2e538bedbc8235499e68312df72', '2026-07-01 20:35:32', 'ok', NULL, '2026-07-06 11:55:44', '2026-07-06 11:55:44', '2026-07-06 11:55:44'),
(32, 67, 50, 'khyrour', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"lf\",\"أعراض غير محددة\"]', 'المريضة أنثى، أُجري الفحص لسبب غير واضح، مع أعراض قصيرة غير محددة. لا توجد معلومات كافية لتحديد الحالة بشكل دقيق.', 0, NULL, '[\"سبب الفحص الدقيق\",\"وصف الأعراض بالتفصيل\",\"نتائج الفحص\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"lf\", \"أعراض غير محددة\"],\n  \"summary\": \"المريضة أنثى، أُجري الفحص لسبب غير واضح، مع أعراض قصيرة غير محددة. لا توجد معلومات كافية لتحديد الحالة بشكل دقيق.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"سبب الفحص الدقيق\", \"وصف الأعراض بالتفصيل\", \"نتائج الفحص\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'b7bbe7a8246625bf3c629b563ce7e6ced2c0e6882022d66207c67f599b14c993', '2026-07-01 19:55:08', 'ok', NULL, '2026-07-06 11:55:46', '2026-07-06 11:55:46', '2026-07-06 11:55:46'),
(33, 66, 50, 'jamila', NULL, NULL, NULL, 'medium', '[]', NULL, 0, NULL, '[]', 0, NULL, NULL, 'llama-3.3-70b-versatile', '0a07cc558c1b43f550f5a14da4bd7151dc8d1dd7e6efa77a169f964d0a4449bb', '2026-07-01 20:31:30', 'error', 'تم تجاوز حدّ الاستخدام مؤقتاً. حاول بعد قليل.', '2026-07-06 11:55:47', '2026-07-06 11:55:47', '2026-07-06 11:55:47'),
(34, 65, 50, 'tppaw', 'Unknown', 'غير محدد', 'Unknown', 'low', '[\"فحص\",\"موعد\"]', 'المرضى أنثى، سبب الفحص غير واضح. لا يوجد موعد قادم.', 0, NULL, '[\"سبب الفحص\",\"تاريخ المرض\",\"تشخيص\"]', 1, 'Archive / Unknown / Low Priority', '{\n  \"specialty\": \"Unknown\",\n  \"specialty_ar\": \"غير محدد\",\n  \"disease_category\": \"Unknown\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص\", \"موعد\"],\n  \"summary\": \"المرضى أنثى، سبب الفحص غير واضح. لا يوجد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"سبب الفحص\", \"تاريخ المرض\", \"تشخيص\"],\n  \"suggested_path\": [\"Archive\", \"Unknown\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'c6a2fcb1e615e3f2f332ab02e9eec349a11cf61a4feff5c769d4e58c548a3601', '2026-07-01 18:01:34', 'ok', NULL, '2026-07-06 11:55:49', '2026-07-06 11:55:49', '2026-07-06 11:55:49'),
(35, 64, 50, 'tp', 'Urology', 'جراحة المسالك البولية', 'Urinary Tract Issue', 'low', '[\"فحص بولي\",\"مسالك بولية\"]', 'المرضى الذكر خضع لفحص بولي. لا توجد معلومات إضافية عن الحالة. لم يتم تحديد موعد قادم.', 0, NULL, '[\"نتائج الفحص\",\"تشخيص المرض\"]', 1, 'Archive / Urology / Low Priority', '{\n  \"specialty\": \"Urology\",\n  \"specialty_ar\": \"جراحة المسالك البولية\",\n  \"disease_category\": \"Urinary Tract Issue\",\n  \"priority\": \"Low\",\n  \"keywords\": [\"فحص بولي\", \"مسالك بولية\"],\n  \"summary\": \"المرضى الذكر خضع لفحص بولي. لا توجد معلومات إضافية عن الحالة. لم يتم تحديد موعد قادم.\",\n  \"followup_required\": false,\n  \"followup\": null,\n  \"missing_info\": [\"نتائج الفحص\", \"تشخيص المرض\"],\n  \"suggested_path\": [\"Archive\", \"Urology\", \"Low Priority\"]\n}', 'llama-3.3-70b-versatile', 'a162372af0fe5722425b776fa8f4853712ff71901f1ae5e2e401095d50655716', '2026-07-01 17:48:35', 'ok', NULL, '2026-07-06 11:55:51', '2026-07-06 11:55:51', '2026-07-06 11:55:51');

-- --------------------------------------------------------

--
-- Structure de la table `ai_medical_reports`
--

CREATE TABLE `ai_medical_reports` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `report_content` mediumtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_medical_reports`
--

INSERT INTO `ai_medical_reports` (`id`, `medical_record_id`, `doctor_id`, `patient_name`, `model`, `report_content`, `created_at`) VALUES
(1, 62, 50, 'robin', 'llama-3.3-70b-versatile', '1) معلومات المريض\n- الاسم: robin\n- الجنس: أنثى\n- تاريخ الزيارة: 2026-06-26 21:36:53\n\n2) ملخص الحالة\nالمريض يعاني من ألم صدري منذ يومين مع ضيق خفيف في التنفس. لديه تاريخ مرضي مع ارتفاع ضغط الدم.\n\n3) الحالة الحالية\nالمريض يعاني من ألم صدري وضيق خفيف في التنفس. ضغط الدم مستقر.\n\n4) أهم الملاحظات الطبية\n- ألم صدري منذ يومين\n- ضيق خفيف في التنفس\n- ضغط الدم مستقر\n- لا توجد حساسية معروفة\n- تاريخ مرضي مع ارتفاع ضغط الدم\n\n5) نتائج الفحص\nلا توجد معلومات كافية\n\n6) تفسير نتائج التحاليل (إن وُجدت)\nنتائج التحاليل: hay، لا توجد معلومات كافية لتفسير هذه النتائج.\n\n7) تفسير نتائج الأشعة (إن وُجدت)\nنتائج الأشعة: hay، لا توجد معلومات كافية لتفسير هذه النتائج.\n\n8) التقييم السريري\nالمريض يعاني من ألم صدري وضيق خفيف في التنفس، مع تاريخ مرضي من ارتفاع ضغط الدم. يُحتاج إلى مزيد من الفحوصات والتحاليل لتقييم الحالة بدقة.\n\n9) التوصيات\nلا توجد معلومات كافية عن الأدوية أو خطة العلاج المحددة، حيث أن المعلومات المتاحة هي \"hay\" فقط.\n\n10) خطة المتابعة\nيجب إجراء مزيد من الفحوصات والتحاليل لتحديد سبب ألم الصدر وضيق التنفس، وتقييم تأثير تاريخ المريض المرضي على حالته الحالية. يُفضل استشارة الطبيب المعالج لتحديد الخطة المثلى للمتابعة والعلاج.\n\nتم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي لمساعدة الطبيب، ولا يُعد بديلاً عن التقييم الطبي النهائي للطبيب.', '2026-06-27 13:15:27'),
(2, 61, 50, 'chikh', 'llama-3.3-70b-versatile', '1) معلومات المريض\n- الاسم: chikh\n- الجنس: ذكر\n- تاريخ الزيارة: 2026-06-26 21:32:13\n\n2) ملخص الحالة\nالمريض يشتكي من حمى وسعال منذ خمسة أيام مع تعب عام.\n\n3) الحالة الحالية\nلا توجد معلومات كافية.\n\n4) أهم الملاحظات الطبية\nالمريض يشكو من حمى وسعال منذ خمسة أيام مع تعب عام.\n\n5) نتائج الفحص\nلا توجد معلومات كافية.\n\n6) تفسير نتائج التحاليل (إن وُجدت)\nنتائج التحاليل: m3lbliiiiiiiiiiiiiiiiiiiiiiiich ؛ m3lbliiiiiiiiiiiiiiiiiiiiiiiich (الحالة: pending)\n\n7) تفسير نتائج الأشعة (إن وُجدت)\nنتائج الأشعة: waaaaaaaaaaaaa ؛ waaaaaaaaaaaaa (الحالة: pending)\n\n8) التقييم السريري\nلا توجد معلومات كافية.\n\n9) التوصيات\nالأدوية / خطة العلاج: m3lbliiiiiiiiiiiiiiiiiiiich\n\n10) خطة المتابعة\nلا توجد معلومات كافية.\n\nتم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي لمساعدة الطبيب، ولا يُعد بديلاً عن التقييم الطبي النهائي للطبيب.', '2026-06-27 13:16:52'),
(4, 49, 50, 'hayk', 'llama-3.3-70b-versatile', '1) معلومات المريض\n- الاسم: hayk\n- الجنس: ذكر\n- تاريخ الزيارة: 2026-06-25 14:21:33\n\n2) ملخص الحالة\nلا توجد معلومات كافية\n\n3) الحالة الحالية\nالم في الراس\n\n4) أهم الملاحظات الطبية\nالم في الراس\n\n5) نتائج الفحص\nلا توجد معلومات كافية\n\n6) تفسير نتائج التحاليل \nلا توجد معلومات كافية\n\n7) تفسير نتائج الأشعة \nلا توجد معلومات كافية\n\n8) التقييم السريري\nلا توجد معلومات كافية\n\n9) التوصيات\nلا توجد معلومات كافية\n\n10) خطة المتابعة\nلا توجد معلومات كافية\n\nتم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي لمساعدة الطبيب، ولا يُعد بديلاً عن التقييم الطبي النهائي للطبيب.', '2026-06-27 13:49:55'),
(5, 46, 50, 'saffae', 'llama-3.3-70b-versatile', '1) معلومات المريض\n- الاسم: saffae\n- الجنس: أنثى\n- تاريخ الزيارة: 2026-06-25 13:31:35\n\n2) ملخص الحالة\nلا توجد معلومات كافية\n\n3) الحالة الحالية\nلا توجد معلومات كافية\n\n4) أهم الملاحظات الطبية\nلا توجد معلومات كافية\n\n5) نتائج الفحص\n- نتائج التحاليل: NFS CRP، الحالة: pending\n- نتائج الأشعة: RX ECHO ABDOMINAUX، الحالة: pending\n\n6) تفسير نتائج التحاليل (إن وُجدت)\nلا توجد معلومات كافية\n\n7) تفسير نتائج الأشعة (إن وُجدت)\nلا توجد معلومات كافية\n\n8) التقييم السريري\nلا توجد معلومات كافية\n\n9) التوصيات\n- الأدوية / خطة العلاج: PARACETAMOL\n\n10) خطة المتابعة\nلا توجد معلومات كافية\n\nتم إنشاء هذا التقرير بواسطة الذكاء الاصطناعي لمساعدة الطبيب، ولا يُعد بديلاً عن التقييم الطبي النهائي للطبيب.', '2026-06-27 14:05:24');

-- --------------------------------------------------------

--
-- Structure de la table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `patient_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `case_type` enum('عادية','مستعجلة','مزمنة') DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `status`, `created_at`, `patient_name`, `phone`, `case_type`, `appointment_date`, `appointment_time`) VALUES
(48, 76, 49, 'confirmed', '2026-05-07 16:31:00', 'kiko', '06554432118', 'مستعجلة', '2026-05-07', '08:08:00'),
(49, 76, 49, 'confirmed', '2026-05-09 14:31:52', 'njet', '06554432118', 'عادية', '2026-05-09', '10:00:00'),
(59, 0, 49, 'completed', '2026-05-09 23:59:57', NULL, NULL, NULL, '2026-05-11', '22:02:00'),
(60, 80, 50, 'confirmed', '2026-05-10 01:14:23', 'naji', '06554432118', 'عادية', '2026-05-10', '22:02:00'),
(61, 62, 55, 'confirmed', '2026-05-10 11:28:19', 'kheira', '06554432118', 'عادية', '2026-05-10', '08:00:00'),
(63, 76, 49, 'confirmed', '2026-05-10 11:32:57', 'sara', '097542287', 'عادية', '2026-05-10', '08:08:00'),
(64, 62, 49, 'confirmed', '2026-05-10 22:58:39', 'najt', '06554432118', 'عادية', '2026-05-11', '22:02:00'),
(65, 62, 49, 'confirmed', '2026-05-11 22:59:57', 'najt', '0986532', 'عادية', '2026-05-12', '22:02:00'),
(66, 86, 49, 'confirmed', '2026-05-11 23:27:51', 'naji', '0986532', 'عادية', '2026-05-12', '08:08:00'),
(73, 62, 49, 'confirmed', '2026-05-16 23:05:00', 'naji', '0986532', 'عادية', '2026-05-17', '09:09:00'),
(74, 62, 50, 'confirmed', '2026-05-17 12:11:06', 'mohamed', '0986532', 'عادية', '2026-05-17', '03:03:00'),
(75, 62, 49, 'confirmed', '2026-05-17 22:51:56', 'najt', '0986532', 'عادية', '2026-05-18', '04:04:00'),
(76, 62, 49, 'completed', '2026-05-18 22:04:51', 'fatima', '0986532', 'عادية', '2026-05-19', '03:03:00'),
(77, 62, 50, 'confirmed', '2026-05-19 07:03:14', 'mohamed', '0986532', 'عادية', '2026-05-19', '22:22:00'),
(78, 62, 49, 'confirmed', '2026-05-20 16:28:08', 'mohamed', '0986532', 'عادية', '2026-05-20', '08:08:00'),
(79, 76, 49, 'confirmed', '2026-05-20 23:57:24', 'njt', '0986532', 'عادية', '2026-05-21', '04:04:00'),
(80, 62, 50, 'confirmed', '2026-05-21 05:43:07', 'mohamed', '0986532', 'عادية', '2026-05-21', '03:03:00'),
(81, 62, 49, 'confirmed', '2026-05-22 15:01:15', 'najt', '0986532', 'عادية', '2026-05-22', '04:04:00'),
(82, 62, 50, 'confirmed', '2026-05-23 21:56:50', 'mohamed', '0986532', 'عادية', '2026-05-23', '03:03:00'),
(83, 62, 49, 'pending', '2026-05-23 21:57:03', 'nadjet', '0986532', 'عادية', NULL, NULL),
(84, 62, 50, 'confirmed', '2026-05-24 09:58:09', 'mohamed', '0986532', 'عادية', '2026-05-24', '07:07:00'),
(85, 80, 50, 'confirmed', '2026-05-25 20:04:08', 'mohamed', '0986532', 'عادية', '2026-05-25', '04:04:00'),
(86, 80, 50, 'confirmed', '2026-05-29 20:46:40', 'WAIL', '0986532', 'عادية', '2026-05-29', '03:04:00'),
(87, 62, 50, 'confirmed', '2026-05-30 14:48:20', 'mohamed', '0986532', 'عادية', '2026-05-30', '07:07:00'),
(88, 62, 50, 'confirmed', '2026-05-31 12:16:52', 'mohamed', '0986532', 'عادية', '2026-05-31', '03:03:00'),
(89, 80, 50, 'completed', '2026-06-25 11:05:52', 'mohammed amin', '0986532', 'عادية', '2026-06-25', '10:00:00'),
(90, 184, 50, 'completed', '2026-06-25 13:51:54', 'naji', '0986532', 'مستعجلة', '2026-06-25', '02:02:00'),
(91, 185, 50, 'confirmed', '2026-06-25 15:04:12', 'ahmed', '0986532', 'عادية', '2026-06-25', '04:04:00'),
(96, 182, 50, 'no_show', '2026-06-26 19:35:25', 'robin', '0986532', 'عادية', '2026-06-26', '22:22:00'),
(97, 185, 50, 'completed', '2026-06-26 19:50:33', 'chikh', '0986532', 'عادية', '2026-06-26', '02:03:00'),
(98, 188, 50, 'confirmed', '2026-07-04 07:55:51', 'fahd', '0986544', 'عادية', '2026-07-05', '01:01:00'),
(99, 188, 50, 'pending', '2026-07-06 12:01:16', 'naji', '09988776', 'عادية', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `archived_records`
--

CREATE TABLE `archived_records` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `birth_date` varchar(100) DEFAULT NULL,
  `medical_condition` text DEFAULT NULL,
  `job_type` varchar(255) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `heart_rate` varchar(50) DEFAULT NULL,
  `temperature` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `archived_records`
--

INSERT INTO `archived_records` (`id`, `medical_record_id`, `patient_name`, `birth_date`, `medical_condition`, `job_type`, `blood_pressure`, `heart_rate`, `temperature`, `created_at`) VALUES
(1, NULL, 'loo', '', '', '', '', '', '', '2026-05-22 16:22:27'),
(7, NULL, 'mejdoud', '', '', '', '', '', '', '2026-05-22 16:57:14'),
(8, NULL, 'karim', '', '', '', '', '', '', '2026-05-22 16:57:44'),
(9, NULL, 'kheira', '', '', '', '', '', '', '2026-05-22 16:58:41'),
(10, NULL, 'yacin', '', '', '', '', '', '', '2026-05-22 17:03:39'),
(31, NULL, 'oiuy', '', '', '', '', '', '', '2026-05-30 00:45:33'),
(32, NULL, 'jiko', '', '', '', '', '', '', '2026-05-30 00:49:12'),
(33, NULL, 'juy', '', '', '', '', '', '', '2026-05-30 00:55:26'),
(34, NULL, 'opo', '', '', '', '', '', '', '2026-05-30 01:08:03'),
(35, NULL, 'lhaj', '', '', '', '', '', '', '2026-05-30 01:18:53'),
(36, NULL, 'kini', '', '', '', '', '', '', '2026-05-30 01:27:45'),
(37, 42, 'Bachir', '10/05/1967', 'السكري والضغط', 'retraiter', '80', '87', '37', '2026-05-30 18:41:06'),
(38, 43, 'najet', '', '', '', '', '', '', '2026-06-24 19:44:00'),
(39, 44, 'najet', '', '', '', '', '', '', '2026-06-24 21:03:34'),
(40, 45, 'lamia', '', '', '', '', '', '', '2026-06-25 11:10:08'),
(41, 46, 'saffae', '', '', '', '', '', '', '2026-06-25 11:31:35'),
(42, 47, 'mahmoud', '', '', '', '', '', '', '2026-06-25 11:48:18'),
(43, 48, 'lilo', '', '', '', '', '', '', '2026-06-25 11:50:36'),
(44, 49, 'hayk', '', '', '', '', '', '', '2026-06-25 12:21:33'),
(45, NULL, 'jiji', '', '', '', '', '', '', '2026-06-26 19:32:14'),
(46, 62, 'robin', '', '', '', '', '', '', '2026-06-26 19:36:53'),
(47, NULL, 'robin', '', '', '', '', '', '', '2026-06-26 19:39:49'),
(48, 61, 'chikh', '', '', '', '', '', '', '2026-06-26 19:52:22'),
(49, 63, 'rahaf', '', '', '', '', '', '', '2026-06-27 16:12:48'),
(50, NULL, 'tp', '', '', '', '', '', '', '2026-07-01 17:48:35'),
(51, NULL, 'tppaw', '', '', '', '', '', '', '2026-07-01 18:01:34'),
(52, NULL, 'jamila', '', '', '', '', '', '', '2026-07-01 19:27:13'),
(53, NULL, 'khyrour', '', '', '', '', '', '', '2026-07-01 19:55:08'),
(54, NULL, 'hakemnadjet', '', '', '', '', '', '', '2026-07-01 20:34:31'),
(55, NULL, 'inomina', '', '', '', '', '', '', '2026-07-01 20:50:01'),
(56, NULL, ' ', '', '', NULL, NULL, NULL, NULL, '2026-07-04 08:02:32'),
(57, NULL, 'oiy', '', '', '', '', '', '', '2026-07-05 23:00:36'),
(58, NULL, ' ', '', '', NULL, NULL, NULL, NULL, '2026-07-06 11:57:15');

-- --------------------------------------------------------

--
-- Structure de la table `civil_protection`
--

CREATE TABLE `civil_protection` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `civil_protection`
--

INSERT INTO `civil_protection` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`) VALUES
(1, 'Protection Civile Sidi Bel Abbes 1', 35.1899, -0.6333, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(2, 'Protection Civile Centre', 35.1918, -0.6375, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(3, 'Protection Civile Amarnas', 35.1545, -0.7025, 'Sidi Bel Abbes', 'Amarnas'),
(4, 'Protection Civile Sidi Lahcene', 35.2055, -0.687, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(5, 'Unité Secours Ouest', 35.1872, -0.627, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(6, 'Protection Civile SBA Nord', 35.1905, -0.634, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(7, 'Protection Civile SBA Est', 35.1922, -0.6365, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(8, 'Protection Civile SBA Sud', 35.188, -0.629, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(9, 'Unité Secours Amarnas 2', 35.1565, -0.7045, 'Sidi Bel Abbes', 'Amarnas'),
(10, 'Centre Intervention Amarnas', 35.158, -0.706, 'Sidi Bel Abbes', 'Amarnas'),
(11, 'Protection Civile Sidi Lahcene 2', 35.2075, -0.6895, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(12, 'Unité Secours Lahcene', 35.209, -0.691, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(13, 'Protection Civile Ouest 2', 35.1865, -0.6265, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(14, 'Centre Secours Central', 35.1898, -0.6312, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(15, 'Unité Intervention Rapide', 35.1915, -0.6335, 'Sidi Bel Abbes', 'Sidi Bel Abbes');

-- --------------------------------------------------------

--
-- Structure de la table `clinics`
--

CREATE TABLE `clinics` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clinics`
--

INSERT INTO `clinics` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`) VALUES
(1, 'Clinique El Rahma', 35.1908, -0.6328, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(2, 'Clinique Ibn Rochd', 35.1922, -0.6368, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(3, 'Clinique Amarnas', 35.1528, -0.7008, 'Sidi Bel Abbes', 'Amarnas'),
(4, 'Clinique Sidi Lahcene', 35.2038, -0.6848, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(5, 'Clinique Echifa', 35.188, -0.629, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(6, 'Clinique El Nour', 35.1895, -0.6315, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(7, 'Clinique El Baraka', 35.1918, -0.6338, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(8, 'Clinique El Hikma', 35.1935, -0.6355, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(9, 'Clinique Amarnas Plus', 35.1545, -0.7025, 'Sidi Bel Abbes', 'Amarnas'),
(10, 'Clinique Chifa Amarnas', 35.1562, -0.7042, 'Sidi Bel Abbes', 'Amarnas'),
(11, 'Clinique Sidi Lahcene Care', 35.2065, -0.6875, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(12, 'Clinique Rahma Lahcene', 35.2082, -0.6892, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(13, 'Clinique Centrale Plus', 35.1875, -0.6275, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(14, 'Clinique El Amal Care', 35.1888, -0.6288, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(15, 'Clinique Ibn Sina Plus', 35.1902, -0.6302, 'Sidi Bel Abbes', 'Sidi Bel Abbes');

-- --------------------------------------------------------

--
-- Structure de la table `clinic_profiles`
--

CREATE TABLE `clinic_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `is_profile_complete` tinyint(1) DEFAULT 0,
  `license_file` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `institution_type` enum('clinic','hospital') NOT NULL DEFAULT 'clinic'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clinic_profiles`
--

INSERT INTO `clinic_profiles` (`id`, `user_id`, `clinic_name`, `license_number`, `wilaya`, `commune`, `is_profile_complete`, `license_file`, `lat`, `lng`, `institution_type`) VALUES
(16, 180, 'jio', '54645454', 'sidi bel abbes', NULL, 1, 'uploads/licenses/1781625792_medchifa_logo.png', 0.00000000, 0.00000000, 'clinic');

-- --------------------------------------------------------

--
-- Structure de la table `clinic_staff`
--

CREATE TABLE `clinic_staff` (
  `id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('doctor','nurse','lab_technician','radiology_technician','pharmacist','receptionist','service_admin') NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `account_status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `specialty` varchar(150) DEFAULT NULL,
  `pharmacy_type` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `previous_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clinic_staff`
--

INSERT INTO `clinic_staff` (`id`, `clinic_id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `service_id`, `account_status`, `created_at`, `specialty`, `pharmacy_type`, `last_login`, `previous_login`) VALUES
(159, 96, 'naji', 'hinanadjet@gmail.com', '0987643', '$2y$10$4hDQ3FqIEtALgMOBrlcN9uiMtJmJF9VjfnRKmZVesHBf4GGwaF9nG', 'nurse', NULL, 'active', '2026-06-10 19:33:14', NULL, NULL, '2026-06-10 21:33:45', NULL),
(178, 180, 'najit', 'hinanadjet@gmail.com', 'dd', '$2y$10$Yps3BU4osNo5uPBmJLsRCejUttBWDhAVqFguF/rdbegR//.k/Kb1K', 'doctor', NULL, 'active', '2026-07-05 15:32:57', 'cardio', NULL, NULL, NULL),
(179, 180, 'mohammed', 'hinanadjet@gmail.com', 'gggg', '$2y$10$G0u8D1w.WRO3VBozPE833OLOmeaMvBXynBYVSsYuCaQu6fRcsKBaG', 'doctor', NULL, 'active', '2026-07-05 17:33:17', 'cardio', NULL, NULL, NULL),
(180, 180, 'info', 'informatiquechat@gmail.com', '0987643', '$2y$10$QJMBjqpSX6dS5zgzrUEoVebyrTxCI.AZx1gGzRLyno9Y1YMN8P5Nu', 'doctor', NULL, 'active', '2026-07-05 17:37:27', 'cardio', NULL, '2026-07-05 19:39:28', NULL),
(182, 180, 'bhyy65', 'nadjethakem11@gmail.com', '0987643', '$2y$10$4P..8xSYJBVPYtEhlmsYF.T.wHBI.SZ.0UwkdZALCDVriO7ELN7WS', 'service_admin', 60, 'active', '2026-07-05 18:59:27', '', '', NULL, NULL),
(183, 180, 'serine', 'sirinesonia346@gmail.com', '0987643', '$2y$10$tl4l6tr7ZQStZ2H0iVz1CusfuED7hxpVPZ4nxaUExKUOXJxjAf.5a', 'nurse', NULL, 'active', '2026-07-05 19:02:54', NULL, NULL, '2026-07-05 21:03:38', NULL),
(184, 180, 'tppaw', 'tppaw401@gmail.com', '0987643', '$2y$10$OOo/6DXs9an8QEjQ0/eCfOD9rlUOSe71sp.eWGMhlxO6fPBnX/cmW', 'doctor', NULL, 'active', '2026-07-05 22:54:51', 'cardio', NULL, '2026-07-06 01:04:34', '2026-07-06 00:55:53'),
(185, 180, 'jiji', 'nadjetjiji703@gmail.com', '0987643', '$2y$10$Icrov.SDjAN2gI8Vs7tQoupVk.S8cs7xN2z2o1qtmZJ3umy3U4Jka', 'doctor', NULL, 'active', '2026-07-05 22:55:33', 'cardio', NULL, '2026-07-06 00:56:14', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `communes`
--

CREATE TABLE `communes` (
  `id` int(11) NOT NULL,
  `wilaya_id` int(11) NOT NULL,
  `name_fr` varchar(100) NOT NULL,
  `name_ar` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `communes`
--

INSERT INTO `communes` (`id`, `wilaya_id`, `name_fr`, `name_ar`) VALUES
(1, 1, 'Adrar', 'أدرار'),
(2, 1, 'Tamest', 'تامست'),
(3, 1, 'Charouine', 'شروين'),
(4, 1, 'Reggane', 'رقان'),
(5, 1, 'In Zghmir', 'إن زغمير'),
(6, 1, 'Tit', 'تيت'),
(7, 1, 'Ksar Kaddour', 'قصر قدور'),
(8, 1, 'Tsabit', 'تسابيت'),
(9, 1, 'Timimoun', 'تيميمون'),
(10, 1, 'Ouled Said', 'أولاد سعيد'),
(11, 1, 'Zaouiet Kounta', 'زاوية كنتة'),
(12, 1, 'Aoulef', 'أولف'),
(13, 1, 'Timokten', 'تيموكتن'),
(14, 1, 'Tinerkouk', 'تينركوك'),
(15, 1, 'Deldoul', 'دلدول'),
(16, 1, 'Sali', 'صالي'),
(17, 1, 'Akabli', 'أقبلي'),
(18, 1, 'Metarfa', 'مطارفة'),
(19, 1, 'Ouled Ahmed Timmi', 'أولاد أحمد تيمي'),
(20, 1, 'Bouda', 'بودة'),
(21, 1, 'Aougrout', 'أوقروت'),
(22, 1, 'Talmine', 'تالمين'),
(23, 1, 'Bordj Badji Mokhtar', 'برج باجي مختار'),
(24, 1, 'Sbaa', 'سبع'),
(25, 1, 'Ouled Aissa', 'أولاد عيسى'),
(26, 1, 'Timiaouine', 'تيميياوين'),
(27, 2, 'Chlef', 'الشلف'),
(28, 2, 'Ténès', 'تنس'),
(29, 2, 'Benairia', 'بنايرية'),
(30, 2, 'El Karimia', 'الكريمية'),
(31, 2, 'Tadjna', 'تاجنة'),
(32, 2, 'Taougrite', 'تاوقريت'),
(33, 2, 'Beni Haoua', 'بني حواء'),
(34, 2, 'Sobha', 'صبحة'),
(35, 2, 'Harchoun', 'هارشون'),
(36, 2, 'Ouled Fares', 'أولاد فارس'),
(37, 2, 'Sidi Akacha', 'سيدي عكاشة'),
(38, 2, 'Boukadir', 'بوقادير'),
(39, 2, 'Beni Rached', 'بني راشد'),
(40, 2, 'Talassa', 'تلعصة'),
(41, 2, 'Herenfa', 'هرنفة'),
(42, 2, 'Oued Goussine', 'وادي قوسين'),
(43, 2, 'Djendel', 'جندل'),
(44, 2, 'El Marsa', 'المرسى'),
(45, 2, 'Oum Drou', 'أم الدروع'),
(46, 2, 'Abou El Hassan', 'أبو الحسن'),
(47, 2, 'Sendjas', 'سنجاس'),
(48, 2, 'Zeboudja', 'الزبوجة'),
(49, 2, 'Ain Merane', 'عين مران'),
(50, 2, 'Ouled Abbes', 'أولاد عباس'),
(51, 2, 'Chorfa', 'الشرفاء'),
(52, 2, 'Labiod Medjadja', 'العبيد مجاجة'),
(53, 2, 'El Hadjadj', 'الحجاج'),
(54, 2, 'Moussadek', 'موساديق'),
(55, 2, 'El Oued', 'الوادي'),
(56, 2, 'Bouzghaia', 'بوزغاية'),
(57, 2, 'Ain Tarma', 'عين طارمة'),
(58, 2, 'Beni Bouattab', 'بني بوعتاب'),
(59, 2, 'Ibn Badis', 'ابن باديس'),
(60, 2, 'Chettia', 'الشطية'),
(61, 2, 'Sidi Abderrahmane', 'سيدي عبد الرحمان'),
(62, 2, 'Remila', 'الرميلة'),
(63, 3, 'Laghouat', 'الأغواط'),
(64, 3, 'Ksar El Hirane', 'قصر الحيران'),
(65, 3, 'Benoud', 'بنود'),
(66, 3, 'Brida', 'بريدة'),
(67, 3, 'Gueltat Sidi Saad', 'قلتة سيدي سعد'),
(68, 3, 'Ain Mahdi', 'عين ماضي'),
(69, 3, 'Tadjemout', 'تاجموت'),
(70, 3, 'Kheneg', 'الخنق'),
(71, 3, 'Hassi Delaa', 'حاسي دلاعة'),
(72, 3, 'Hassi R Mel', 'حاسي الرمل'),
(73, 3, 'Ain Sidi Ali', 'عين سيدي علي'),
(74, 3, 'Beidha', 'البيضاء'),
(75, 3, 'Aflou', 'أفلو'),
(76, 3, 'El Ghicha', 'الغيشة'),
(77, 3, 'Hadj Mechri', 'الحاج المشري'),
(78, 3, 'Sebgag', 'سبقاق'),
(79, 3, 'Taouiala', 'تاويالة'),
(80, 3, 'Tadjrouna', 'تاجرونة'),
(81, 3, 'El Houaita', 'الحويطة'),
(82, 3, 'Sidi Makhlouf', 'سيدي مخلوف'),
(83, 3, 'Oued Morra', 'وادي مرة'),
(84, 3, 'Oued M Zi', 'وادي مزي'),
(85, 3, 'El Assafia', 'الصفيصفة'),
(86, 3, 'Ain Touta', 'عين التوتة'),
(87, 4, 'Oum El Bouaghi', 'أم البواقي'),
(88, 4, 'Ain Babouche', 'عين بابوش'),
(89, 4, 'Berriche', 'بريش'),
(90, 4, 'Ain Zitoun', 'عين الزيتون'),
(91, 4, 'Ouled Zouai', 'أولاد زواي'),
(92, 4, 'Behir Chergui', 'بهير الشرقي'),
(93, 4, 'Ksar Sbahi', 'قصر صباحي'),
(94, 4, 'Ain Kercha', 'عين كرشة'),
(95, 4, 'Hanchir Toumghani', 'هنشير التومغني'),
(96, 4, 'El Amiria', 'الأميرية'),
(97, 4, 'Ain Fakroun', 'عين فكرون'),
(98, 4, 'Rahia', 'الراحية'),
(99, 4, 'Meskiana', 'مسكيانة'),
(100, 4, 'Ain Babouche', 'عين بابوش'),
(101, 4, 'Souk Naamane', 'سوق نعمان'),
(102, 4, 'Zorg', 'زرق'),
(103, 4, 'El Fedjoudj', 'الفجوج'),
(104, 4, 'Ouled Gacem', 'أولاد قاسم'),
(105, 4, 'El Harmilia', 'الحرملية'),
(106, 5, 'Batna', 'باتنة'),
(107, 5, 'Ghassira', 'غسيرة'),
(108, 5, 'Maafa', 'معافة'),
(109, 5, 'Merouana', 'مروانة'),
(110, 5, 'Seriana', 'سريانة'),
(111, 5, 'Menaa', 'منعة'),
(112, 5, 'El Madher', 'المعذر'),
(113, 5, 'Tazoult', 'تازولت'),
(114, 5, 'N Gaous', 'نقاوس'),
(115, 5, 'Guigba', 'قيقبة'),
(116, 5, 'Inoughissen', 'إينوغيسن'),
(117, 5, 'Ouyoun El Assafir', 'عيون العصافير'),
(118, 5, 'Djerma', 'جرمة'),
(119, 5, 'Bitam', 'بيطام'),
(120, 5, 'Metkaouak', 'متكاوك'),
(121, 5, 'Ouled Aouf', 'أولاد عوف'),
(122, 5, 'Boulhilat', 'بولحيلات'),
(123, 5, 'Lazrou', 'لازرو'),
(124, 5, 'Timgad', 'تيمقاد'),
(125, 5, 'Ras El Aioun', 'رأس العيون'),
(126, 5, 'Chir', 'شير'),
(127, 5, 'Ouled Sellam', 'أولاد سلام'),
(128, 5, 'Tigherghar', 'تيغرغار'),
(129, 5, 'Aïn Djasser', 'عين جاسر'),
(130, 5, 'Ouled Si Slimane', 'أولاد سي سليمان'),
(131, 5, 'Ghoufi', 'غوفي'),
(132, 5, 'Aïn Yagout', 'عين ياقوت'),
(133, 5, 'Fesdis', 'فسديس'),
(134, 5, 'El Hassi', 'الحاسي'),
(135, 5, 'Oued El Ma', 'وادي الماء'),
(136, 5, 'Bouzina', 'بوزينة'),
(137, 5, 'Chemorra', 'شمورة'),
(138, 5, 'Barika', 'بريكة'),
(139, 5, 'Djezzar', 'الجزار'),
(140, 5, 'Larbaa', 'الأربعاء'),
(141, 5, 'Boumagueur', 'بومقور'),
(142, 5, 'Ain Touta', 'عين التوتة'),
(143, 5, 'Tilatou', 'تيلاطو'),
(144, 5, 'Ichmoul', 'إيشمول'),
(145, 5, 'Foum Toub', 'فم الطوب'),
(146, 5, 'Boulhaf Dyr', 'بولحاف الدير'),
(147, 5, 'Hidoussa', 'حيدوسة'),
(148, 5, 'Arris', 'آريس'),
(149, 5, 'Kimmel', 'كيمل'),
(150, 5, 'Teniet El Abed', 'تنيت العابد'),
(151, 5, 'Oued Chaaba', 'وادي الشعبة'),
(152, 5, 'Taxlent', 'تكسلنت'),
(153, 5, 'Gosbat', 'قصبة'),
(154, 5, 'Ouled Fadel', 'أولاد فاضل'),
(155, 5, 'Rahbat', 'رحبة'),
(156, 5, 'Talkhamt', 'تلخمت'),
(157, 5, 'Lemsane', 'لمسان'),
(158, 5, 'Ksar Bellezma', 'قصر بلزمة'),
(159, 5, 'Seggana', 'سقانة'),
(160, 5, 'Ain Skhouna', 'عين سخونة'),
(161, 5, 'Zanat El Beida', 'ذانات البيضاء'),
(162, 5, 'Boumia', 'بومية'),
(163, 5, 'Oued El Maadi', 'وادي المعادي'),
(164, 5, 'Bitam', 'بيطام'),
(165, 5, 'Abdelkader Azil', 'عبد القادر عزيل'),
(166, 5, 'El Korichi', 'القريشي'),
(167, 6, 'Béjaïa', 'بجاية'),
(168, 6, 'Amizour', 'أميزور'),
(169, 6, 'Ferraouen', 'فراون'),
(170, 6, 'Taourirt Ighil', 'تاوريرت إغيل'),
(171, 6, 'Chellata', 'شلاطة'),
(172, 6, 'Tamokra', 'تاموقرة'),
(173, 6, 'Timezrit', 'تيمزريت'),
(174, 6, 'Souk El Tenine', 'سوق الإثنين'),
(175, 6, 'Melbou', 'ملبو'),
(176, 6, 'Akbou', 'أقبو'),
(177, 6, 'Seddouk', 'صدوق'),
(178, 6, 'Tazmalt', 'تازمالت'),
(179, 6, 'Ighil Ali', 'إغيل علي'),
(180, 6, 'Feraoun', 'فرعون'),
(181, 6, 'Tizi N Berber', 'تيزي نبربر'),
(182, 6, 'Chemini', 'شميني'),
(183, 6, 'Souk Oufella', 'سوق أوفلة'),
(184, 6, 'Taskriout', 'تاسقريوت'),
(185, 6, 'Tibane', 'تيبان'),
(186, 6, 'El Kseur', 'القصر'),
(187, 6, 'Barbacha', 'بربشة'),
(188, 6, 'Beni Djellil', 'بني جليل'),
(189, 6, 'Aït Smail', 'آيت إسماعيل'),
(190, 6, 'Ouzellaguen', 'أوزلاقن'),
(191, 6, 'Ighrem', 'أغرم'),
(192, 6, 'Aokas', 'أوقاس'),
(193, 6, 'Beni Maouche', 'بني معوش'),
(194, 6, 'Darguina', 'درقينة'),
(195, 6, 'Sidi Aich', 'سيدي عيش'),
(196, 6, 'Aït Rzine', 'آيت رزين'),
(197, 6, 'Adekar', 'أدكار'),
(198, 6, 'Akfadou', 'أكفادو'),
(199, 6, 'Leflaye', 'لفلاي'),
(200, 6, 'Kherrata', 'خراطة'),
(201, 6, 'Draa El Caïd', 'ضرع القايد'),
(202, 6, 'Tizi Ghennif', 'تيزي غنيف'),
(203, 6, 'Kendira', 'كنديرة'),
(204, 6, 'Tifra', 'تيفرة'),
(205, 6, 'Ighil Bazin', 'إغيل بازن'),
(206, 6, 'Bouhamza', 'بوحمزة'),
(207, 6, 'Beni Ksila', 'بني قسيلة'),
(208, 6, 'Oued Ghir', 'وادي غير'),
(209, 6, 'Boukhelifa', 'بوخليفة'),
(210, 6, 'Tala Hamza', 'تالة حمزة'),
(211, 6, 'Aït Smail', 'آيت إسماعيل'),
(212, 6, 'Fritissa', 'فريتيسة'),
(213, 6, 'Beni Mellikeche', 'بني مليكاش'),
(214, 6, 'Semaoun', 'سمعون'),
(215, 6, 'Sidi Aich', 'سيدي عيش'),
(216, 6, 'Aït Smail', 'آيت إسماعيل'),
(217, 6, 'Meghaier', 'مغاير'),
(218, 7, 'Biskra', 'بسكرة'),
(219, 7, 'Oumache', 'أوماش'),
(220, 7, 'Branis', 'براني'),
(221, 7, 'El Kantara', 'القنطرة'),
(222, 7, 'Sidi Okba', 'سيدي عقبة'),
(223, 7, 'Ain Naga', 'عين ناقة'),
(224, 7, 'Zeribet El Oued', 'زريبة الوادي'),
(225, 7, 'El Haouch', 'الحوش'),
(226, 7, 'Lichana', 'ليشانة'),
(227, 7, 'Ourlal', 'أورلال'),
(228, 7, 'Tolga', 'طولقة'),
(229, 7, 'Bouchagroun', 'بوشقرون'),
(230, 7, 'M Chounech', 'مشونش'),
(231, 7, 'El Ghrous', 'الغروس'),
(232, 7, 'Foughala', 'فوغالة'),
(233, 7, 'Bordj Ben Azzouz', 'برج بن عزوز'),
(234, 7, 'Djemorah', 'جمورة'),
(235, 7, 'Ain Ben Naoui', 'عين بن ناوي'),
(236, 7, 'Lioua', 'ليوة'),
(237, 7, 'Chetma', 'شتمة'),
(238, 7, 'Xeria', 'خيري'),
(239, 7, 'Sidi Khaled', 'سيدي خالد'),
(240, 7, 'Doucen', 'دوسن'),
(241, 7, 'Ras El Miad', 'رأس الميعاد'),
(242, 7, 'El Feidh', 'الفيض'),
(243, 7, 'Besbes', 'الببس'),
(244, 7, 'El Hadjeb', 'الحاجب'),
(245, 7, 'Sidi Ghzel', 'سيدي غزال'),
(246, 7, 'Ain Zaatout', 'عين زعطوط'),
(247, 7, 'El Outaya', 'العوطاية'),
(248, 7, 'Oued El Djia', 'وادي الجيا'),
(249, 7, 'Mekhadma', 'مخادمة'),
(250, 8, 'Béchar', 'بشار'),
(251, 8, 'Erg Ferradj', 'عرق فراج'),
(252, 8, 'Lahmar', 'لحمر'),
(253, 8, 'Meridja', 'المريجة'),
(254, 8, 'Igli', 'إقلي'),
(255, 8, 'Beni Ounif', 'بني ونيف'),
(256, 8, 'Boukais', 'بوكايس'),
(257, 8, 'Mogheul', 'مقرة'),
(258, 8, 'Ain Skhouna', 'عين سخونة'),
(259, 8, 'Ouled Khoudir', 'أولاد خدير'),
(260, 8, 'Tabelbala', 'تبلبالة'),
(261, 8, 'Taghit', 'تاغيت'),
(262, 8, 'Timoudi', 'تيمودي'),
(263, 8, 'Kerzaz', 'كرزاز'),
(264, 8, 'Oulad Khodeir', 'أولاد خدير'),
(265, 8, 'Abadla', 'عبادلة'),
(266, 8, 'Beni Abbes', 'بني عباس'),
(267, 8, 'El Ouata', 'الواطة'),
(268, 8, 'Kenedsa', 'كنادسة'),
(269, 8, 'Mechraa Houari Boumediene', 'مشرع هواري بومدين'),
(270, 9, 'Blida', 'البليدة'),
(271, 9, 'Chebli', 'الشبلي'),
(272, 9, 'Mouzaia', 'الموزاية'),
(273, 9, 'Oued El Alleug', 'وادي العلايق'),
(274, 9, 'Chrea', 'الشريعة'),
(275, 9, 'Bougara', 'بوغار'),
(276, 9, 'Larbaatache', 'الأربعطاش'),
(277, 9, 'Ouled Slama', 'أولاد سلامة'),
(278, 9, 'El Affroun', 'الأفرون'),
(279, 9, 'Beni Tamou', 'بني تامو'),
(280, 9, 'Hammam Melouane', 'حمام الملوان'),
(281, 9, 'Ben Khellil', 'بن خليل'),
(282, 9, 'Souhane', 'سوحان'),
(283, 9, 'Meftah', 'المفتاح'),
(284, 9, 'Guerrouaou', 'قروواو'),
(285, 9, 'Ain Romana', 'عين الرمانة'),
(286, 9, 'Bougara', 'بوغار'),
(287, 9, 'Beni Mered', 'بني مراد'),
(288, 9, 'Bouarfa', 'بوعرفة'),
(289, 9, 'Boufarik', 'بوفاريك'),
(290, 9, 'El Afroun', 'الأفرون'),
(291, 9, 'Oued Djer', 'وادي جر'),
(292, 9, 'Ouled Yaich', 'أولاد يعيش'),
(293, 9, 'Beni Mered', 'بني مراد'),
(294, 10, 'Bouira', 'البويرة'),
(295, 10, 'Ain Bessem', 'عين بسام'),
(296, 10, 'El Asnam', 'الأصنام'),
(297, 10, 'Guerrouma', 'قروما'),
(298, 10, 'Souk El Khemis', 'سوق الخميس'),
(299, 10, 'El Hakimia', 'الحاكمية'),
(300, 10, 'Taguedit', 'تاقديت'),
(301, 10, 'El Khabouzia', 'الخابوزية'),
(302, 10, 'Oued El Berdi', 'وادي البردي'),
(303, 10, 'Bordj Okhriss', 'برج أخريص'),
(304, 10, 'El Adjiba', 'العجيبة'),
(305, 10, 'El Esnam', 'الأصنام'),
(306, 10, 'Dirah', 'ديرة'),
(307, 10, 'Ain Laloui', 'عين لالوي'),
(308, 10, 'Chorfa', 'الشرفاء'),
(309, 10, 'Bouderbala', 'بودربالة'),
(310, 10, 'Haizer', 'هيزر'),
(311, 10, 'Kadiria', 'قادرية'),
(312, 10, 'Maala', 'معلى'),
(313, 10, 'Taghzout', 'تاغزوت'),
(314, 10, 'Ridane', 'ريدان'),
(315, 10, 'Beni Korsi', 'بني كرسي'),
(316, 10, 'Hadjera Zerga', 'حجرة الزرقاء'),
(317, 10, 'Aghbalou', 'أغبالو'),
(318, 10, 'Ahl El Kseur', 'أهل القصر'),
(319, 10, 'Mchedallah', 'مشدالة'),
(320, 10, 'Saharidj', 'صحاريج'),
(321, 10, 'Maalou', 'معلو'),
(322, 10, 'Bechloul', 'بشلول'),
(323, 10, 'Bir Ghbalou', 'بئر غبالو'),
(324, 10, 'M Chedallah', 'مشدالة'),
(325, 10, 'Draa El Diss', 'ضرع الديس'),
(326, 10, 'Ait Laaziz', 'آيت لعزيز'),
(327, 10, 'Aomar', 'عمار'),
(328, 10, 'Oued Lakhdhar', 'وادي الأخضر'),
(329, 10, 'Lakhdaria', 'الأخضرية'),
(330, 10, 'Sour El Ghouzlane', 'سور الغزلان'),
(331, 10, 'Zbarbar', 'زبار'),
(332, 10, 'Kherbouche', 'خربوشة'),
(333, 10, 'Mezdour', 'مزدور'),
(334, 10, 'El Hachimia', 'الهاشمية'),
(335, 10, 'Ath Mansour', 'آث منصور'),
(336, 10, 'Ouled Rached', 'أولاد راشد'),
(337, 10, 'Dechmia', 'الدشمية'),
(338, 10, 'Ain El Hadjar', 'عين الحجر'),
(339, 10, 'Haniff', 'حنيف'),
(340, 10, 'Maamora', 'معمورة'),
(341, 10, 'Boukram', 'بوكرام'),
(342, 10, 'Ain Touila', 'عين توتة'),
(343, 11, 'Tamanrasset', 'تمنراست'),
(344, 11, 'Abalessa', 'أبالسة'),
(345, 11, 'In Ghar', 'إن قار'),
(346, 11, 'In Guezzam', 'إن قزام'),
(347, 11, 'Tazrouk', 'تازروك'),
(348, 11, 'Tin Zaouatine', 'تين زواتين'),
(349, 11, 'Idless', 'إيدلس'),
(350, 11, 'Ain Salah', 'عين صالح'),
(351, 11, 'Ain Guezzam', 'عين قزام'),
(352, 11, 'Foggaret Ez Zoua', 'فقارة الزوى'),
(353, 12, 'Tébessa', 'تبسة'),
(354, 12, 'Bir El Ater', 'بئر العاتر'),
(355, 12, 'Cheria', 'الشريعة'),
(356, 12, 'Ain Zerga', 'عين الزرقاء'),
(357, 12, 'El Ogla', 'العقلة'),
(358, 12, 'Morsott', 'المرصاط'),
(359, 12, 'El Malabiod', 'الملابيض'),
(360, 12, 'Oum Ali', 'أم علي'),
(361, 12, 'Bir Dheheb', 'بئر الذهب'),
(362, 12, 'Negrine', 'نقرين'),
(363, 12, 'Bekkaria', 'البكارية'),
(364, 12, 'Boukhadra', 'بوخضرة'),
(365, 12, 'Ouenza', 'وانزة'),
(366, 12, 'El Houidjbet', 'الحويجبات'),
(367, 12, 'El Ogla El Malha', 'العقلة الملحاء'),
(368, 12, 'Safsaf El Ouesra', 'صفصاف الوسرة'),
(369, 12, 'El Meridj', 'المريج'),
(370, 12, 'Thlidjene', 'ثليجان'),
(371, 12, 'Hammamet', 'حمامات'),
(372, 12, 'El Kouif', 'الكويف'),
(373, 12, 'Tébessa', 'تبسة'),
(374, 12, 'Ain Chennacheune', 'عين شنشان'),
(375, 13, 'Tlemcen', 'تلمسان'),
(376, 13, 'Beni Mester', 'بني مستار'),
(377, 13, 'Aïn Tallout', 'عين تالوت'),
(378, 13, 'Remchi', 'الرمشي'),
(379, 13, 'El Fehoul', 'الفحول'),
(380, 13, 'Sebdou', 'سبدو'),
(381, 13, 'Beni Snous', 'بني سنوس'),
(382, 13, 'Beni Boussaid', 'بني بوسعيد'),
(383, 13, 'Ain Ghoraba', 'عين الغرابة'),
(384, 13, 'Chetouane', 'شتوان'),
(385, 13, 'Mansourah', 'المنصورة'),
(386, 13, 'Béni Semiel', 'بني سميل'),
(387, 13, 'Amieur', 'عميور'),
(388, 13, 'Ain Youcef', 'عين يوسف'),
(389, 13, 'Zidoune', 'زيدون'),
(390, 13, 'Fellaoucene', 'فلاوسن'),
(391, 13, 'Azails', 'عزايل'),
(392, 13, 'Beni Ouarsous', 'بني وارسوس'),
(393, 13, 'Sidi Medjahed', 'سيدي المجاهد'),
(394, 13, 'Beni Khellad', 'بني خلاد'),
(395, 13, 'Ain Nehala', 'عين النهالة'),
(396, 13, 'Ghazaouet', 'الغزوات'),
(397, 13, 'Souahlia', 'صواحلية'),
(398, 13, 'Msirda Fouaga', 'مسيردة الفواقة'),
(399, 13, 'Ain Ferroukh', 'عين فروخة'),
(400, 13, 'Bab El Assa', 'باب العسة'),
(401, 13, 'Dar Yaghmouracen', 'دار يغمراسن'),
(402, 13, 'Ain Kebira', 'عين الكبيرة'),
(403, 13, 'Nedroma', 'ندرومة'),
(404, 13, 'El Gor', 'الغور'),
(405, 13, 'Honaïne', 'هنين'),
(406, 13, 'Tienet', 'تيانت'),
(407, 13, 'Oued Chouly', 'وادي الشولي'),
(408, 13, 'Ain Bouzekri', 'عين بوزكري'),
(409, 13, 'El Aricha', 'العريشة'),
(410, 13, 'Souk Tleta', 'سوق الثلاثاء'),
(411, 13, 'Sidi Abdelli', 'سيدي عبدلي'),
(412, 13, 'Sebaa Chioukh', 'سبعة شيوخ'),
(413, 13, 'Oued Issem', 'وادي الإسم'),
(414, 13, 'Bensekrane', 'بن سكران'),
(415, 13, 'El Bouihi', 'البويهي'),
(416, 13, 'Hammam Boughrara', 'حمام بوغرارة'),
(417, 13, 'Sidi Djillali', 'سيدي جيلالي'),
(418, 13, 'Beni Bahdel', 'بني بهدل'),
(419, 13, 'Ouled Riyah', 'أولاد رياح'),
(420, 13, 'Terny Beni Hdiel', 'ترني بني هديل'),
(421, 13, 'Zenata', 'زناتة'),
(422, 13, 'Ain Fezza', 'عين فزة'),
(423, 13, 'Ouled Mimoun', 'أولاد ميمون'),
(424, 14, 'Tiaret', 'تيارت'),
(425, 14, 'Mehdia', 'مهدية'),
(426, 14, 'Ain Dheb', 'عين الذهب'),
(427, 14, 'Rahouia', 'راهوية'),
(428, 14, 'Medroussa', 'مدروسة'),
(429, 14, 'Hamadia', 'حمادية'),
(430, 14, 'Ain Kermes', 'عين كرمس'),
(431, 14, 'Ksar Chellala', 'قصر الشلالة'),
(432, 14, 'Mellakou', 'ملاكو'),
(433, 14, 'Dahmouni', 'دحموني'),
(434, 14, 'Oued Lilli', 'وادي ليلي'),
(435, 14, 'Mahia', 'ماهية'),
(436, 14, 'Sidi Hosni', 'سيدي حسني'),
(437, 14, 'Djillali Ben Amar', 'جيلالي بن عمار'),
(438, 14, 'Frenda', 'فرندة'),
(439, 14, 'Ain El Hadid', 'عين الحديد'),
(440, 14, 'Ouled Djellal', 'أولاد جلال'),
(441, 14, 'Nadorah', 'النادور'),
(442, 14, 'Guertoufa', 'قرطوفة'),
(443, 14, 'Sidi Ali Mellal', 'سيدي علي ملال'),
(444, 14, 'Meghila', 'مغيلة'),
(445, 14, 'Rechaiga', 'رشايقة'),
(446, 14, 'Naima', 'نعيمة'),
(447, 14, 'Serghine', 'سرغين'),
(448, 14, 'El Hamadna', 'الحمادنة'),
(449, 14, 'Ain El Hadid', 'عين الحديد'),
(450, 14, 'Tagdempt', 'تاقدمت'),
(451, 14, 'Takhemaret', 'تاخمرت'),
(452, 14, 'Zmalet El Emir Abdelkader', 'زمالة الأمير عبد القادر'),
(453, 14, 'Sougueur', 'سوقر'),
(454, 14, 'Sidi Bakhti', 'سيدي بختي'),
(455, 14, 'Sebaine', 'سبعين'),
(456, 14, 'Ain Bouchekif', 'عين بوشقيف'),
(457, 14, 'Sidi Abderrahmane', 'سيدي عبد الرحمان'),
(458, 14, 'Medrissa', 'مدريسة'),
(459, 14, 'Bougara', 'بوغار'),
(460, 14, 'Aïn Zarit', 'عين زاريت'),
(461, 14, 'Beni Djaad', 'بني جعاد'),
(462, 14, 'Sebaïne', 'سبعين'),
(463, 14, 'Tousnina', 'توسنينة'),
(464, 14, 'Rosfa', 'روسفة'),
(465, 14, 'Faidja', 'فيضة'),
(466, 14, 'Chehaima', 'الشهيمة'),
(467, 15, 'Tizi Ouzou', 'تيزي وزو'),
(468, 15, 'Aïn El Hammam', 'عين الحمام'),
(469, 15, 'Akbil', 'أقبيل'),
(470, 15, 'Ait Agouacha', 'آيت أقواش'),
(471, 15, 'Beni Douala', 'بني دوالة'),
(472, 15, 'Beni Zmenzer', 'بني زمنزر'),
(473, 15, 'Iferhounene', 'إيفرحونان'),
(474, 15, 'Yatafen', 'يطافن'),
(475, 15, 'Beni Yenni', 'بني يني'),
(476, 15, 'Ain Zaouia', 'عين الزاوية'),
(477, 15, 'Tizi Rached', 'تيزي راشد'),
(478, 15, 'Bouzeguene', 'بوزقن'),
(479, 15, 'Illoula Oumalou', 'إيلولة أومالو'),
(480, 15, 'Ait Boumahdi', 'آيت بومهدي'),
(481, 15, 'Ouadhias', 'الواضية'),
(482, 15, 'Ait Yahia', 'آيت يحيى'),
(483, 15, 'Ait Yahia Moussa', 'آيت يحيى موسى'),
(484, 15, 'Ait Mahmoud', 'آيت محمود'),
(485, 15, 'Maatkas', 'معاتقة'),
(486, 15, 'Ait Bouyoucef', 'آيت بو يوسف'),
(487, 15, 'Ait Toudert', 'آيت تودرت'),
(488, 15, 'Abi Youcef', 'آبي يوسف'),
(489, 15, 'Tala Ata', 'تالة عطة'),
(490, 15, 'Boghni', 'بوغني'),
(491, 15, 'Frikat', 'فريقات'),
(492, 15, 'Assi Youcef', 'عيسى يوسف'),
(493, 15, 'Aghribs', 'أغريب'),
(494, 15, 'Iflissen', 'إيفليسن'),
(495, 15, 'Timizart', 'تيميزارت'),
(496, 15, 'Sidi Naamane', 'سيدي نعمان'),
(497, 15, 'Azzazga', 'عزازقة'),
(498, 15, 'Semaoune', 'سماعن'),
(499, 15, 'Freha', 'فريحة'),
(500, 15, 'Tigzirt', 'تيقزيرت'),
(501, 15, 'Dra El Mizan', 'ذراع الميزان'),
(502, 15, 'Ouguenoun', 'أوقنون'),
(503, 15, 'Tizi Ghennif', 'تيزي غنيف'),
(504, 15, 'Ait Oumalou', 'آيت أومالو'),
(505, 15, 'Iboudraren', 'إيبودرارن'),
(506, 15, 'Irdjen', 'إيردجن'),
(507, 15, 'Tirourda', 'تيرورة'),
(508, 15, 'Akerrou', 'أكرو'),
(509, 15, 'Zekri', 'زكري'),
(510, 15, 'Larbaa Nath Irathen', 'الأربعاء ناث إيراثن'),
(511, 15, 'Tizi Rached', 'تيزي راشد'),
(512, 15, 'Ait Khelili', 'آيت خليلي'),
(513, 15, 'Souk El Tnine Ait El Hadj', 'سوق الإثنين آيت الحاج'),
(514, 15, 'Ait Ouacifs', 'آيت واسيف'),
(515, 15, 'Souamaa', 'سواماع'),
(516, 15, 'Ait Aggouacha', 'آيت أقواش'),
(517, 15, 'Mekla', 'مكلى'),
(518, 15, 'Ouaguenoun', 'أوقنون'),
(519, 15, 'Ait Chafaa', 'آيت شفعة'),
(520, 15, 'Imsouhel', 'إيمسوحل'),
(521, 15, 'Beni Aissi', 'بني عيسى'),
(522, 15, 'Beni Ziki', 'بني زيكي'),
(523, 15, 'Sidi Naâmane', 'سيدي نعمان'),
(524, 15, 'Draa Ben Khedda', 'ذراع بن خدة'),
(525, 15, 'Souk El Djemaa', 'سوق الجمعة'),
(526, 15, 'Tirmitine', 'تيرميتين'),
(527, 15, 'Makouda', 'مقودة'),
(528, 15, 'Douaouda', 'دواودة'),
(529, 15, 'Redjaouna', 'رجاونة'),
(530, 15, 'Tadmaït', 'تادمايت'),
(531, 16, 'Alger Centre', 'الجزائر الوسطى'),
(532, 16, 'Sidi M Hamed', 'سيدي امحمد'),
(533, 16, 'El Madania', 'المدنية'),
(534, 16, 'Belouizdad', 'بلوزداد'),
(535, 16, 'Bab El Oued', 'باب الوادي'),
(536, 16, 'Bologhine', 'بولوغين'),
(537, 16, 'Casbah', 'القصبة'),
(538, 16, 'Oued Koriche', 'وادي قريش'),
(539, 16, 'Bir Mourad Rais', 'بئر مراد رايس'),
(540, 16, 'El Biar', 'البيار'),
(541, 16, 'Bouzareah', 'بوزريعة'),
(542, 16, 'Birkhadem', 'بئر خادم'),
(543, 16, 'El Harrach', 'الحراش'),
(544, 16, 'Baraki', 'براقي'),
(545, 16, 'Oued Smar', 'وادي سمار'),
(546, 16, 'Bachdjerrah', 'باش جراح'),
(547, 16, 'Hussein Dey', 'حسين داي'),
(548, 16, 'Kouba', 'كوبا'),
(549, 16, 'Bourouba', 'بروبة'),
(550, 16, 'Dar El Beida', 'دار البيضاء'),
(551, 16, 'Bab Ezzouar', 'باب الزوار'),
(552, 16, 'Ben Aknoun', 'بن عكنون'),
(553, 16, 'Dely Ibrahim', 'دالي إبراهيم'),
(554, 16, 'Hammamet', 'حمامات'),
(555, 16, 'Raïs Hamidou', 'رايس حميدو'),
(556, 16, 'Djasr El Maarouf', 'جسر المعروف'),
(557, 16, 'Mohammadia', 'المحمدية'),
(558, 16, 'Bordj El Bahri', 'برج البحري'),
(559, 16, 'El Marsa', 'المرسى'),
(560, 16, 'Ain Taya', 'عين طاية'),
(561, 16, 'Bordj El Kiffan', 'برج الكيفان'),
(562, 16, 'El Magharia', 'المقرية'),
(563, 16, 'Birkhadem', 'بئر خادم'),
(564, 16, 'El Mouradia', 'المرادية'),
(565, 16, 'Hydra', 'حيدرة'),
(566, 16, 'Mossapeau', 'موسابو'),
(567, 16, 'Bains Romains', 'الحمامات الرومانية'),
(568, 16, 'Cheraga', 'الشراقة'),
(569, 16, 'Ain Benian', 'عين بنيان'),
(570, 16, 'Staoueli', 'الصطاولي'),
(571, 16, 'Zeralda', 'زرالدة'),
(572, 16, 'Mahelma', 'ماهلمة'),
(573, 16, 'Rahmania', 'الرحمانية'),
(574, 16, 'Souidania', 'الصويدانية'),
(575, 16, 'Ouled Fayet', 'أولاد فايت'),
(576, 16, 'Draria', 'الدرارية'),
(577, 16, 'El Achour', 'الأشور'),
(578, 16, 'Tessala El Merdja', 'تسالة المرجة'),
(579, 16, 'Sidi Moussa', 'سيدي موسى'),
(580, 16, 'Ain Taya', 'عين طاية'),
(581, 16, 'Rouiba', 'الرويبة'),
(582, 16, 'Reghaïa', 'الرغاية'),
(583, 16, 'Ain El Benian', 'عين البنيان'),
(584, 16, 'Douera', 'دويرة'),
(585, 16, 'Birtouta', 'بئر توتة'),
(586, 16, 'Tessala El Merdja', 'تسالة المرجة'),
(587, 17, 'Djelfa', 'الجلفة'),
(588, 17, 'Moudjbara', 'موجبارة'),
(589, 17, 'El Idrissia', 'الإدريسية'),
(590, 17, 'Aïn El Ibel', 'عين البيل'),
(591, 17, 'Charef', 'الشارف'),
(592, 17, 'Birine', 'بيرين'),
(593, 17, 'Bouira Lahdeb', 'بويرة الأحداب'),
(594, 17, 'Zaccar', 'زكار'),
(595, 17, 'El Guedid', 'الجديد'),
(596, 17, 'Hassi El Euch', 'حاسي العش'),
(597, 17, 'Messaad', 'مسعد'),
(598, 17, 'Guernini', 'الغرنيني'),
(599, 17, 'Selmana', 'سلمانة'),
(600, 17, 'Ain Oussara', 'عين وسارة'),
(601, 17, 'Benhar', 'بنهار'),
(602, 17, 'El Malah', 'الملح'),
(603, 17, 'El Hassi', 'الحاسي'),
(604, 17, 'Douis', 'دويس'),
(605, 17, 'Dar Chioukh', 'دار شيوخ'),
(606, 17, 'Chouileh', 'الشويلح'),
(607, 17, 'El Khemis', 'الخميس'),
(608, 17, 'Beni Yacoub', 'بني يعقوب'),
(609, 17, 'Guettara', 'قطارة'),
(610, 17, 'Sidi Baizid', 'سيدي بيزيد'),
(611, 17, 'Leksour', 'القصور'),
(612, 17, 'Faidh El Botma', 'فيض البطمة'),
(613, 17, 'Bordj Ain Naâm', 'برج عين النعام'),
(614, 17, 'Ain El Maabed', 'عين المعابد'),
(615, 17, 'Had Sahary', 'الحد الصحاري'),
(616, 17, 'Zaâfrane', 'الزعفران'),
(617, 17, 'Boukhezana', 'بوخزانة'),
(618, 17, 'Hassi Fedoul', 'حاسي فدول'),
(619, 17, 'Aïn Chouhada', 'عين الشهداء'),
(620, 17, 'Oum Laadham', 'أم الأذهام'),
(621, 17, 'Znaghia', 'الزناغية'),
(622, 17, 'Sidi Ladjel', 'سيدي لجدل'),
(623, 17, 'Hassi El Euch', 'حاسي العش'),
(624, 17, 'El Idrissia', 'الإدريسية'),
(625, 17, 'M Liliha', 'مليلحة'),
(626, 17, 'Ain Feka', 'عين فكة'),
(627, 17, 'Deldoul', 'دلدول'),
(628, 17, 'Ain Oussera', 'عين وسارة'),
(629, 18, 'Jijel', 'جيجل'),
(630, 18, 'Erraguene', 'الراقنة'),
(631, 18, 'El Aouana', 'العوانة'),
(632, 18, 'Ziama Mansouriah', 'ضيامة منصورية'),
(633, 18, 'Taher', 'الطاهير'),
(634, 18, 'Emir Abdelkader', 'الأمير عبد القادر'),
(635, 18, 'Chahna', 'الشحنة'),
(636, 18, 'El Milia', 'الميلية'),
(637, 18, 'Sidi Maarouf', 'سيدي معروف'),
(638, 18, 'Settara', 'الستارة'),
(639, 18, 'El Ancer', 'العنصر'),
(640, 18, 'Ouled Yahia Khadrouch', 'أولاد يحيى خدروش'),
(641, 18, 'Boudriaa Ben Yadjis', 'بودريعة بن يعجيس'),
(642, 18, 'Kaous', 'قاوس'),
(643, 18, 'Ghebala', 'الغبالة'),
(644, 18, 'Boussif Ouled Askeur', 'بوسيف أولاد عسكر'),
(645, 18, 'El Aouana', 'العوانة'),
(646, 18, 'Djimla', 'جيملة'),
(647, 18, 'Selma Benziada', 'سلمى بن زيادة'),
(648, 18, 'Bordj T Har', 'برج الطهر'),
(649, 18, 'Texenna', 'تاكسنة'),
(650, 18, 'Djimla', 'جيملة'),
(651, 18, 'Ouled Rabah', 'أولاد رباح'),
(652, 18, 'Ain Makhlouf', 'عين مخلوف'),
(653, 18, 'Ain Soltane', 'عين السلطان'),
(654, 18, 'El Kennar Nouchfi', 'القنار نوشفي'),
(655, 18, 'Ouled Sidi Abdelaziz', 'أولاد سيدي عبد العزيز'),
(656, 18, 'Sidi Abdelaziz', 'سيدي عبد العزيز'),
(657, 19, 'Sétif', 'سطيف'),
(658, 19, 'Ain El Kebira', 'عين الكبيرة'),
(659, 19, 'Ain Arnat', 'عين أرنات'),
(660, 19, 'El Eulma', 'العلمة'),
(661, 19, 'Ain Azel', 'عين آزال'),
(662, 19, 'Guidjel', 'ڤيجل'),
(663, 19, 'Ain Oulmane', 'عين ولمان'),
(664, 19, 'Guenzet', 'قنزت'),
(665, 19, 'Ain El Hdjar', 'عين الحجر'),
(666, 19, 'Amoucha', 'عموشة'),
(667, 19, 'Babor', 'بابور'),
(668, 19, 'Bougaa', 'بوقاعة'),
(669, 19, 'Bir El Arch', 'بئر العرش'),
(670, 19, 'Bouandas', 'بوعنداس'),
(671, 19, 'Beni Aziz', 'بني عزيز'),
(672, 19, 'Beni Chebana', 'بني شبانة'),
(673, 19, 'Beni Ourtilane', 'بني ورتيلان'),
(674, 19, 'Beni Mouhli', 'بني موحلي'),
(675, 19, 'Boutaleb', 'بوطالب'),
(676, 19, 'Bir Haddada', 'بئر حدادة'),
(677, 19, 'Dehamcha', 'الدهامشة'),
(678, 19, 'Djemila', 'جميلة'),
(679, 19, 'El Ouldja', 'الولجة'),
(680, 19, 'Talaifacene', 'تالايفاسن'),
(681, 19, 'Tachouda', 'تاشودة'),
(682, 19, 'Hammam Sokhna', 'حمام السخنة'),
(683, 19, 'Hammam Guergour', 'حمام ڤرڤور'),
(684, 19, 'Harbil', 'حربيل'),
(685, 19, 'Hamma', 'الحمة'),
(686, 19, 'Ksar El Abtal', 'قصر الأبطال'),
(687, 19, 'Maaouia', 'معاوية'),
(688, 19, 'Mezloug', 'مزلوق'),
(689, 19, 'Ouled Addouane', 'أولاد عدوان'),
(690, 19, 'Ouled Si Ahmed', 'أولاد سي أحمد'),
(691, 19, 'Ouled Tebben', 'أولاد تبن'),
(692, 19, 'Ras El Oued', 'رأس الوادي'),
(693, 19, 'Salah Bey', 'صالح باي'),
(694, 19, 'Tala Ifacen', 'تالا إيفاسن'),
(695, 19, 'Taya', 'الطاية'),
(696, 19, 'Tizi N Bechar', 'تيزي نبشار'),
(697, 19, 'El Tlelat', 'التلات'),
(698, 19, 'Zit El Batel', 'زيت الباطل'),
(699, 19, 'Ain Legradj', 'عين لقراج'),
(700, 19, 'Ain Sebt', 'عين السبت'),
(701, 19, 'Beidha Bordj', 'البيضاء برج'),
(702, 19, 'Belaa', 'بلعاء'),
(703, 19, 'Oued Bared', 'وادي البارد'),
(704, 19, 'Ain Roua', 'عين الروى'),
(705, 19, 'Draa Kebila', 'ضرع القبيلة'),
(706, 19, 'Guellal', 'قلال'),
(707, 19, 'Bousselam', 'بوسلام'),
(708, 19, 'Bir El Arch', 'بئر العرش'),
(709, 19, 'Aïn Lahdjar', 'عين الحجر'),
(710, 19, 'Oued El Bared', 'وادي البارد'),
(711, 19, 'El Ouricia', 'الأوريسية'),
(712, 20, 'Saïda', 'سعيدة'),
(713, 20, 'Aïn El Hadjar', 'عين الحجر'),
(714, 20, 'Ouled Khaled', 'أولاد خالد'),
(715, 20, 'Moulay Larbi', 'مولاي العربي'),
(716, 20, 'Doui Thabet', 'ضوي ثابت'),
(717, 20, 'Sidi Boubekeur', 'سيدي بوبكر'),
(718, 20, 'El Hassasna', 'الحساسنة'),
(719, 20, 'Maamora', 'معمورة'),
(720, 20, 'Hadj Mechri', 'حاج مشري'),
(721, 20, 'Youb', 'يوب'),
(722, 20, 'Tircine', 'تيرسين'),
(723, 20, 'Ain Skhouna', 'عين السخونة'),
(724, 20, 'Sidi Amar', 'سيدي عمار'),
(725, 20, 'Ouled Brahim', 'أولاد إبراهيم'),
(726, 20, 'Sidi Ahmed', 'سيدي أحمد'),
(727, 21, 'Skikda', 'سكيكدة'),
(728, 21, 'Ben Azzouz', 'بن عزوز'),
(729, 21, 'El Harrouch', 'الحروش'),
(730, 21, 'Zerdazas', 'الزرذازة'),
(731, 21, 'Ouled Attia', 'أولاد عطية'),
(732, 21, 'Aïn Zouit', 'عين زويت'),
(733, 21, 'El Marsa', 'المرسى'),
(734, 21, 'Tamalous', 'تمالوس'),
(735, 21, 'Kanoua', 'قنواع'),
(736, 21, 'Oum Toub', 'أم الطيوب'),
(737, 21, 'Beni Zid', 'بني زيد'),
(738, 21, 'Ramdane Djamel', 'رمضان جمال'),
(739, 21, 'Azzaba', 'عزابة'),
(740, 21, 'Salah Bouchaour', 'صالح بوشعور'),
(741, 21, 'Essebt', 'السبت'),
(742, 21, 'Ain Charchar', 'عين شرشار'),
(743, 21, 'Collo', 'القل'),
(744, 21, 'Kerkera', 'كرقرة'),
(745, 21, 'Beni Oulbane', 'بني ولبان'),
(746, 21, 'Ain Kechra', 'عين كشرة'),
(747, 21, 'Ouled Hebaba', 'أولاد حبابة'),
(748, 21, 'Sidi Mezghiche', 'سيدي مزغيش'),
(749, 21, 'Cheraia', 'الشرايع'),
(750, 21, 'Emdjez Edchich', 'أمجاز الدشيش'),
(751, 21, 'Beni Bechir', 'بني بشير'),
(752, 21, 'El Hadaiek', 'الحدائق'),
(753, 21, 'Fil Fila', 'فيل فيلة'),
(754, 21, 'Stoura', 'الستورة'),
(755, 21, 'Djendel Saadi Mohamed', 'جندل سعيد محمد'),
(756, 21, 'Oued Zhour', 'وادي الزهور'),
(757, 21, 'Hamadi Krouma', 'حمادي كرومة'),
(758, 21, 'Khennouf Sidi Goufi', 'خنوف سيدي قفي'),
(759, 21, 'Ain Bouziane', 'عين بوزيان'),
(760, 21, 'Beni Oulbane', 'بني ولبان'),
(761, 21, 'El Ghedir', 'الغدير'),
(762, 21, 'Zitouna', 'الزيتونة'),
(763, 21, 'Ouldja Boulbalout', 'الولجة بولبالوت'),
(764, 21, 'El Mecheraa', 'المشرع'),
(765, 21, 'Djaoua', 'جاوة'),
(766, 22, 'Sidi Bel Abbès', 'سيدي بلعباس'),
(767, 22, 'Tessala', 'تسالة'),
(768, 22, 'Amarnas', 'العمارنة'),
(769, 22, 'Tilmouni', 'تيلموني'),
(770, 22, 'Ain El Berd', 'عين البيرد'),
(771, 22, 'Sidi Ali Benyoub', 'سيدي علي بن يوب'),
(772, 22, 'Merine', 'مرين'),
(773, 22, 'Tenira', 'تنيرة'),
(774, 22, 'Hassi Zehana', 'حاسي زهانة'),
(775, 22, 'Mostefa Ben Brahim', 'مصطفى بن إبراهيم'),
(776, 22, 'Boudjebha El Bordj', 'بوجبهة البرج'),
(777, 22, 'Ras El Ma', 'رأس الماء'),
(778, 22, 'Sfisef', 'صفيصيف'),
(779, 22, 'Oued Sebaa', 'وادي الصبع'),
(780, 22, 'Aïn Adden', 'عين العدن'),
(781, 22, 'Marhoum', 'مرحوم'),
(782, 22, 'Moulay Slissen', 'مولاي سليسن'),
(783, 22, 'Telagh', 'تلاغ'),
(784, 22, 'Ain Tindamine', 'عين التندامين'),
(785, 22, 'Aïn Kada', 'عين قادة'),
(786, 22, 'Sidi Brahim', 'سيدي إبراهيم'),
(787, 22, 'Sidi Khaled', 'سيدي خالد'),
(788, 22, 'Sidi Chaib', 'سيدي شعيب'),
(789, 22, 'Ain Thrid', 'عين تريد'),
(790, 22, 'Badredine El Mokrani', 'بدر الدين المقراني'),
(791, 22, 'Bir El Hammam', 'بئر الحمام'),
(792, 22, 'Mezaourou', 'مزاورو'),
(793, 22, 'Oued Sebbah', 'وادي السبع'),
(794, 22, 'Redjem Demouche', 'رجم الدموش'),
(795, 22, 'Aïn Sekhouna', 'عين السخونة'),
(796, 22, 'El Hacaiba', 'الحصيبة'),
(797, 22, 'Lamtar', 'لمطار'),
(798, 22, 'Zerouala', 'الزرواله'),
(799, 22, 'Sidi Ali Boussidi', 'سيدي علي بوسيدي'),
(800, 22, 'Ben Badis', 'بن باديس'),
(801, 22, 'Boukanoun', 'بوقنون'),
(802, 22, 'Oued Taoura', 'وادي التاورة'),
(803, 22, 'Chouala', 'شوالة'),
(804, 22, 'Lehssasna', 'الحساسنة'),
(805, 22, 'Ain Trid', 'عين تريد'),
(806, 22, 'Oued Sebbah', 'وادي السبع'),
(807, 22, 'Tafessour', 'تافسور'),
(808, 22, 'Tabia', 'طابية'),
(809, 22, 'Hadjrat Ennous', 'حجرة النوس'),
(810, 22, 'Guettarnia', 'قيطارنة'),
(811, 22, 'Benachiba Chelia', 'بناشيبة شلية'),
(812, 22, 'Ain Kada', 'عين قادة'),
(813, 22, 'Ben Badis', 'بن باديس'),
(814, 22, 'Sidi Lahcene', 'سيدي لحسن'),
(815, 23, 'Annaba', 'عنابة'),
(816, 23, 'Berrahhal', 'بررحال'),
(817, 23, 'Eulma', 'العلمة'),
(818, 23, 'El Bouni', 'البوني'),
(819, 23, 'Oued El Aneb', 'وادي العنب'),
(820, 23, 'Cheurfa', 'الشرفاء'),
(821, 23, 'Seraïdi', 'سراييدي'),
(822, 23, 'Aïn Berda', 'عين بردة'),
(823, 23, 'Chetaïbi', 'شطيبي'),
(824, 23, 'Sidi Amar', 'سيدي عمار'),
(825, 23, 'El Hadjar', 'الحجار'),
(826, 23, 'Treat', 'تريعات'),
(827, 23, 'Ain El Berda', 'عين البردة'),
(828, 24, 'Guelma', 'قالمة'),
(829, 24, 'Nechmaya', 'النشماية'),
(830, 24, 'Roknia', 'ركنيان'),
(831, 24, 'Oued Zenati', 'وادي الزناتي'),
(832, 24, 'Ras El Agba', 'رأس العقبة'),
(833, 24, 'Hammam N Bails', 'حمام النبايل'),
(834, 24, 'Hammam Maskhoutine', 'حمام مسخوطين'),
(835, 24, 'Belkheir', 'بلخير'),
(836, 24, 'Héliopolis', 'هيليوبوليس'),
(837, 24, 'Ain Ben Beida', 'عين بن بيضاء'),
(838, 24, 'Khezara', 'خزارة'),
(839, 24, 'Ain Makhlouf', 'عين مخلوف'),
(840, 24, 'Oued Fragha', 'وادي فراغة'),
(841, 24, 'Bordj Sabat', 'برج سباط'),
(842, 24, 'Ain Herodj', 'عين الهيرودج'),
(843, 24, 'El Fedjoudj', 'الفجوج'),
(844, 24, 'Bou Hachana', 'بوحشانة'),
(845, 24, 'Medjez Amar', 'مجاز عمار'),
(846, 24, 'Medjez Sfa', 'مجاز الصفاء'),
(847, 24, 'Bouchegouf', 'بوشقوف'),
(848, 24, 'Ain Sandel', 'عين الصندل'),
(849, 24, 'Beni Mezline', 'بني مزلين'),
(850, 24, 'Rebaou', 'الربعاوي'),
(851, 24, 'Tama', 'تامة'),
(852, 24, 'Sellaoua Announa', 'سلاوة عنونة'),
(853, 24, 'Ain Ben Beida', 'عين بن بيضاء'),
(854, 24, 'Oued Cheham', 'وادي الشاهم'),
(855, 24, 'Ain Reggada', 'عين رقادة'),
(856, 24, 'Bouati Mahmoud', 'بواطي محمود'),
(857, 24, 'Dahouara', 'الضواهرة'),
(858, 24, 'Ben Djerrah', 'بن جراح'),
(859, 24, 'Boumahra Ahmed', 'بومهرة أحمد'),
(860, 25, 'Constantine', 'قسنطينة'),
(861, 25, 'El Khroub', 'الخروب'),
(862, 25, 'Ain Abid', 'عين عبيد'),
(863, 25, 'Zighoud Youcef', 'زيغود يوسف'),
(864, 25, 'Beni Hamidane', 'بني حميدان'),
(865, 25, 'Ouled Rahmoune', 'أولاد رحمون'),
(866, 25, 'Ain Smara', 'عين سمارة'),
(867, 25, 'Hamma Bouziane', 'حامة بوزيان'),
(868, 25, 'Ibn Badis', 'ابن باديس'),
(869, 25, 'Didouche Mourad', 'ديدوش مراد'),
(870, 25, 'El Hamma', 'الحمة'),
(871, 25, 'Beni Mehenna', 'بني مهنة');

-- --------------------------------------------------------

--
-- Structure de la table `consultation_cases`
--

CREATE TABLE `consultation_cases` (
  `id` int(11) NOT NULL,
  `case_number` varchar(30) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `assigned_doctor_id` int(11) DEFAULT NULL,
  `assigned_doctor_type` enum('clinic_staff','private') NOT NULL DEFAULT 'clinic_staff',
  `consultation_scope` enum('internal','external') NOT NULL,
  `consultation_type` enum('medical_opinion','urgent_opinion','case_discussion','patient_transfer','radiology_review','lab_review','follow_up') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('normal','urgent','critical') DEFAULT 'normal',
  `status` enum('new','in_review','answered','closed') DEFAULT 'new',
  `hide_patient_identity` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation_cases`
--

INSERT INTO `consultation_cases` (`id`, `case_number`, `clinic_id`, `patient_id`, `created_by`, `assigned_doctor_id`, `assigned_doctor_type`, `consultation_scope`, `consultation_type`, `title`, `description`, `priority`, `status`, `hide_patient_identity`, `created_at`, `updated_at`, `closed_at`) VALUES
(1, 'CASE-2026-5694CD', 80, 0, 80, 81, 'private', 'external', 'medical_opinion', 'nhh', 'gff', 'normal', 'closed', 0, '2026-07-04 22:42:29', '2026-07-05 12:38:26', '2026-07-05 12:38:26'),
(2, 'CASE-2026-D3DAE9', 80, 187, 80, 160, 'clinic_staff', 'external', 'urgent_opinion', 'nhyio', '1234', 'urgent', 'closed', 1, '2026-07-04 22:56:29', '2026-07-05 12:38:23', '2026-07-05 12:38:23'),
(3, 'CASE-2026-2643A7', 80, 0, 80, 189, 'private', 'external', 'medical_opinion', 'cdkajfi', 'dagj/lea', 'normal', 'closed', 0, '2026-07-05 11:11:14', '2026-07-05 12:38:19', '2026-07-05 12:38:19'),
(4, 'CASE-2026-6A7FEA', 80, 0, 80, 81, 'private', 'external', 'medical_opinion', 'htete', 'rhruyg', 'normal', 'closed', 0, '2026-07-05 11:15:18', '2026-07-05 12:38:15', '2026-07-05 12:38:15'),
(5, 'CASE-2026-61F8A8', 80, 0, 80, 189, 'private', 'external', 'medical_opinion', 'cf', 'cf', 'normal', 'closed', 0, '2026-07-05 11:53:26', '2026-07-05 12:38:12', '2026-07-05 12:38:12'),
(6, 'CASE-2026-98C8F5', 80, 0, 80, 189, 'private', 'external', 'medical_opinion', 'ret', 'ret', 'normal', 'closed', 0, '2026-07-05 12:23:21', '2026-07-05 12:28:21', '2026-07-05 12:28:21'),
(7, 'CASE-2026-07C42E', 80, 186, 80, 81, 'private', 'external', 'medical_opinion', 'dqw', 'esw', 'normal', 'closed', 0, '2026-07-05 12:28:48', '2026-07-05 12:39:13', '2026-07-05 12:39:13'),
(8, 'CASE-2026-1636A8', 80, 0, 80, 189, 'private', 'external', 'medical_opinion', 'hkm', 'hkm', 'normal', 'closed', 0, '2026-07-05 15:50:25', '2026-07-05 17:29:07', '2026-07-05 17:29:07'),
(9, 'CASE-2026-1BB3BB', 80, 0, 80, 198, 'private', 'external', 'medical_opinion', 'hu', 'hu', 'normal', 'closed', 0, '2026-07-05 21:06:41', '2026-07-05 21:15:10', '2026-07-05 21:15:10'),
(10, 'CASE-2026-546272', 80, 185, 80, 198, 'private', 'external', 'medical_opinion', 'dw', 'dw', 'normal', 'closed', 0, '2026-07-05 21:15:49', '2026-07-05 23:26:27', '2026-07-05 23:26:27'),
(11, 'CASE-2026-6BCE85', 80, 185, 80, 198, 'private', 'external', 'medical_opinion', 'njjjjjjj', 'njjjjj', 'normal', 'closed', 0, '2026-07-05 21:19:18', '2026-07-05 23:26:07', '2026-07-05 23:26:07'),
(12, 'CASE-2026-63864F', 80, 185, 80, 198, 'private', 'external', 'medical_opinion', 'jiji', 'jiji', 'normal', 'new', 0, '2026-07-05 21:23:34', '2026-07-05 23:49:34', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `consultation_messages`
--

CREATE TABLE `consultation_messages` (
  `id` int(11) NOT NULL,
  `consultation_case_id` int(11) NOT NULL,
  `sender_type` enum('clinic_staff','private') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('text','image') NOT NULL DEFAULT 'text',
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation_messages`
--

INSERT INTO `consultation_messages` (`id`, `consultation_case_id`, `sender_type`, `sender_id`, `message`, `type`, `file_path`, `created_at`) VALUES
(1, 9, 'private', 198, 'slm', 'text', NULL, '2026-07-05 23:07:24'),
(2, 9, 'private', 80, 'oui', 'text', NULL, '2026-07-05 23:07:51'),
(3, 10, 'private', 198, 'k', 'text', NULL, '2026-07-05 23:16:15'),
(4, 10, 'private', 80, 'll', 'text', NULL, '2026-07-05 23:16:36'),
(5, 12, 'private', 198, 'ji', 'text', NULL, '2026-07-05 23:24:08'),
(6, 12, 'private', 80, 'oui', 'text', NULL, '2026-07-05 23:24:29'),
(7, 2, 'private', 80, 'mm', 'text', NULL, '2026-07-06 01:39:20'),
(8, 12, 'private', 80, 'mm', 'text', NULL, '2026-07-06 10:54:41');

-- --------------------------------------------------------

--
-- Structure de la table `consultation_participants`
--

CREATE TABLE `consultation_participants` (
  `id` int(11) NOT NULL,
  `consultation_case_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `doctor_type` enum('clinic_staff','private') NOT NULL DEFAULT 'clinic_staff',
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `consultation_participants`
--

INSERT INTO `consultation_participants` (`id`, `consultation_case_id`, `doctor_id`, `doctor_type`, `added_by`, `created_at`) VALUES
(1, 12, 180, 'clinic_staff', 80, '2026-07-06 08:40:09');

-- --------------------------------------------------------

--
-- Structure de la table `daily_journal`
--

CREATE TABLE `daily_journal` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `mood` tinyint(4) DEFAULT NULL,
  `feel_text` text DEFAULT NULL,
  `bp` varchar(20) DEFAULT NULL,
  `sugar` varchar(20) DEFAULT NULL,
  `heart_rate` varchar(20) DEFAULT NULL,
  `temperature` varchar(10) DEFAULT NULL,
  `spo2` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `symptoms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`symptoms`)),
  `pain_level` tinyint(4) DEFAULT 0,
  `medication` varchar(10) DEFAULT NULL,
  `sleep_hours` tinyint(4) DEFAULT NULL,
  `sleep_quality` varchar(20) DEFAULT NULL,
  `water_cups` tinyint(4) DEFAULT 0,
  `activity` varchar(20) DEFAULT NULL,
  `nutrition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nutrition`)),
  `notes` text DEFAULT NULL,
  `health_score` tinyint(4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `daily_journal`
--

INSERT INTO `daily_journal` (`id`, `user_id`, `entry_date`, `mood`, `feel_text`, `bp`, `sugar`, `heart_rate`, `temperature`, `spo2`, `weight`, `symptoms`, `pain_level`, `medication`, `sleep_hours`, `sleep_quality`, `water_cups`, `activity`, `nutrition`, `notes`, `health_score`, `created_at`, `updated_at`) VALUES
(1, 62, '2026-05-02', 5, 'good', '80', '100', '70', '37', '98', '70', '[]', 0, 'yes', 9, 'excellent', 5, 'medium', '[]', 'good', 90, '2026-05-02 16:41:37', '2026-05-02 18:39:54'),
(2, 76, '2026-05-02', 5, '', '', '', '', '', '', '', '[]', 0, 'yes', 7, 'excellent', 4, 'low', '[]', '', 95, '2026-05-02 18:20:56', '2026-05-02 18:20:56'),
(3, 62, '2026-05-03', 5, '', '80', '80', '60', '37', '90', '60', '[]', 4, 'late', 7, 'good', 5, 'medium', '[]', '', 70, '2026-05-03 14:00:07', '2026-05-03 14:00:07'),
(4, 62, '2026-05-05', 3, '', '30', '90', '60', '36', '70', '60', '[]', 3, 'late', 7, 'fair', 5, 'low', '[]', '', 52, '2026-05-05 01:48:51', '2026-05-05 02:02:13'),
(5, 76, '2026-05-05', 5, '', '', '', '', '', '', '', '[]', 0, 'late', 9, 'excellent', 3, 'low', '[]', '', 82, '2026-05-05 13:28:09', '2026-05-05 13:28:09'),
(6, 62, '2026-05-06', 5, '', '90', '00', '72', '36', '50', '60', '[]', 4, 'late', 17, 'good', 5, 'medium', '[]', '', 37, '2026-05-06 22:04:44', '2026-05-06 22:04:44'),
(7, 76, '2026-05-11', 4, '', '70', '100', '70', '37', '98', '60', '[]', 5, 'late', 7, 'fair', 6, 'medium', '[]', '', 77, '2026-05-11 13:40:50', '2026-05-11 13:40:50'),
(8, 80, '2026-05-26', 4, '', '120', '110', '72', '37.0', '98', '60', '[]', 0, 'yes', 7, 'good', 5, '', '[\"healthy\"]', '', 100, '2026-05-26 00:56:59', '2026-05-26 01:04:32');

-- --------------------------------------------------------

--
-- Structure de la table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(20) DEFAULT NULL,
  `workplace` varchar(150) DEFAULT NULL,
  `is_profile_complete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `experience` int(11) DEFAULT 0,
  `license_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `license_number`, `workplace`, `is_profile_complete`, `created_at`, `lat`, `lng`, `wilaya`, `commune`, `specialty`, `specialty_id`, `experience`, `license_file`) VALUES
(50, 80, '9999999', 'sidi bel abbess', 1, '2026-05-05 14:03:14', 0, 0, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'cardiologue', NULL, 0, 'uploads/licenses/license_6a33cdaf1196e.png'),
(51, 81, '3456', 'sidi bel abbess', 1, '2026-05-06 12:01:06', 35.20585685186316, -0.6264937525962126, NULL, NULL, 'genicologue', NULL, 0, NULL),
(72, 198, '54645454', 'sidi bel abbess', 1, '2026-07-05 19:25:18', 0, 0, NULL, NULL, 'cardiologue', NULL, 0, 'uploads/licenses/license_6a4aafb9a070b.png'),
(73, 199, '9999999', 'sidi bel abbess', 1, '2026-07-06 11:44:46', 0, 0, NULL, NULL, 'cardiologue', NULL, 0, 'uploads/licenses/license_6a4b95569fc07.png');

-- --------------------------------------------------------

--
-- Structure de la table `donors`
--

CREATE TABLE `donors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `donors`
--

INSERT INTO `donors` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`, `blood_type`) VALUES
(1, 'Donneur Karim', 35.1896, -0.631, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'A+'),
(2, 'Donneuse Sara', 35.1915, -0.6348, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'O+'),
(3, 'Donneur Amarnas', 35.1535, -0.7018, 'Sidi Bel Abbes', 'Amarnas', 'B+'),
(4, 'Donneuse Sidi Lahcene', 35.2048, -0.6865, 'Sidi Bel Abbes', 'Sidi Lahcene', 'AB+'),
(5, 'Donneur Yacine', 35.1878, -0.6288, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'O-'),
(6, 'Donneur Ahmed', 35.1901, -0.631, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'A+'),
(7, 'Donneuse Lina', 35.192, -0.633, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'O+'),
(8, 'Donneur Samir', 35.1945, -0.6355, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'B+'),
(9, 'Donneur Amarnas 2', 35.154, -0.702, 'Sidi Bel Abbes', 'Amarnas', 'A-'),
(10, 'Donneuse Amel', 35.156, -0.704, 'Sidi Bel Abbes', 'Amarnas', 'O-'),
(11, 'Donneur Lahcene 2', 35.206, -0.687, 'Sidi Bel Abbes', 'Sidi Lahcene', 'AB+'),
(12, 'Donneuse Ikram', 35.208, -0.689, 'Sidi Bel Abbes', 'Sidi Lahcene', 'B-'),
(13, 'Donneur Yacine 2', 35.187, -0.627, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'O+'),
(14, 'Donneuse Sara 2', 35.1895, -0.629, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'A+'),
(15, 'Donneur Karim 2', 35.191, -0.6305, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'AB-');

-- --------------------------------------------------------

--
-- Structure de la table `fiche_traitement`
--

CREATE TABLE `fiche_traitement` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `fiche_diagnostic` text DEFAULT NULL,
  `fiche_medications` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fiche_traitement`
--

INSERT INTO `fiche_traitement` (`id`, `medical_record_id`, `fiche_diagnostic`, `fiche_medications`, `created_at`, `updated_at`) VALUES
(4, 42, 'ارتفاع ضغط الدم تحت المتابعة مع مراقبة العلامات الحيوية', 'Paracetamol 500mg\r\n1 comprimé – 3 fois/jour – 5 jours\n\n[01/06/2026 22:18]\nepoprofine', '2026-05-30 18:41:06', '2026-06-01 20:18:13'),
(5, 44, 'maridd', 'paracetamol 3 fois par jour', '2026-06-24 21:03:34', '2026-06-24 21:03:34'),
(6, 45, 'kihyg', '8hr', '2026-06-25 11:10:08', '2026-06-25 11:10:08'),
(7, 46, 'NCURLVF', 'PARACETAMOL IPOPROFINE DOLIPRANE', '2026-06-25 11:31:35', '2026-06-25 11:31:35'),
(8, 47, 'vkjv\'pfw', 'prctmol', '2026-06-25 11:48:18', '2026-06-25 11:48:18');

-- --------------------------------------------------------

--
-- Structure de la table `labs`
--

CREATE TABLE `labs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `labs`
--

INSERT INTO `labs` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`) VALUES
(1, 'Laboratoire El Fajr', 35.19, -0.6315, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(2, 'Laboratoire Analyse Plus', 35.1928, -0.6372, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(3, 'Lab Amarnas', 35.152, -0.7005, 'Sidi Bel Abbes', 'Amarnas'),
(4, 'Lab Sidi Lahcene', 35.205, -0.686, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(5, 'Laboratoire Centrale', 35.1884, -0.6297, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(6, 'Laboratoire El Hayat', 35.2001, -0.6401, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(7, 'Laboratoire Ibn Rochd', 35.1985, -0.632, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(8, 'Laboratoire El Amal Plus', 35.1902, -0.6205, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(9, 'Lab BioTest', 35.21, -0.65, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(10, 'Laboratoire El Chifa Plus', 35.18, -0.61, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(11, 'Laboratoire Rahma', 35.15, -0.7, 'Sidi Bel Abbes', 'Amarnas'),
(12, 'Lab Amarnas Plus', 35.1555, -0.705, 'Sidi Bel Abbes', 'Amarnas'),
(13, 'Laboratoire Sidi Lahcene Plus', 35.2055, -0.69, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(14, 'Lab El Nour', 35.207, -0.695, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(15, 'Laboratoire Médical El Wafa', 35.17, -0.66, 'Sidi Bel Abbes', 'Sfisef'),
(16, 'Lab Analyse Moderne', 35.175, -0.665, 'Sidi Bel Abbes', 'Sfisef'),
(17, 'Laboratoire Central Plus', 35.185, -0.63, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(18, 'Lab Diagnostic Pro', 35.188, -0.635, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(19, 'Laboratoire BioLab', 35.192, -0.638, 'Sidi Bel Abbes', 'Sidi Bel Abbes');

-- --------------------------------------------------------

--
-- Structure de la table `lab_profiles`
--

CREATE TABLE `lab_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lab_name` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `is_profile_complete` tinyint(1) DEFAULT 0,
  `license_file` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lab_requests`
--

CREATE TABLE `lab_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `analysis_text` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lab_requests`
--

INSERT INTO `lab_requests` (`id`, `patient_id`, `doctor_id`, `analysis_text`, `status`, `created_at`) VALUES
(1, 43, 50, 'nfs', 'pending', '2026-06-24 19:44:24'),
(2, 46, 50, 'NFS CRP', 'pending', '2026-06-25 11:31:35'),
(3, 47, 50, 'nfs', 'pending', '2026-06-25 11:48:18'),
(4, 185, 50, 'nfs', 'pending', '2026-06-25 18:50:54'),
(5, 185, 50, 'nchlh ykhdem', 'pending', '2026-06-25 18:53:22'),
(6, 55, 50, 'nchlh ykhdem', 'pending', '2026-06-26 11:54:16'),
(7, 57, 50, 'ta3dil hblni', 'pending', '2026-06-26 12:43:27'),
(8, 61, 50, 'm3lbliiiiiiiiiiiiiiiiiiiiiiiich', 'pending', '2026-06-26 19:51:42');

-- --------------------------------------------------------

--
-- Structure de la table `maintenance_log`
--

CREATE TABLE `maintenance_log` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(200) DEFAULT NULL,
  `admin_role` varchar(100) DEFAULT NULL,
  `action_type` enum('enable','disable') NOT NULL,
  `maint_type` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration_min` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `maintenance_log`
--

INSERT INTO `maintenance_log` (`id`, `admin_id`, `admin_name`, `admin_role`, `action_type`, `maint_type`, `reason`, `started_at`, `ended_at`, `duration_min`, `created_at`) VALUES
(1, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'fjuhnblhn', '2026-06-11 12:34:36', '2026-06-11 12:37:42', 3, '2026-06-11 10:34:37'),
(2, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'fjuhnblhn', '2026-06-11 12:56:35', '2026-06-11 12:56:37', 0, '2026-06-11 10:56:35'),
(3, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'fjuhnblhn', '2026-06-11 12:56:38', '2026-06-11 13:12:18', 16, '2026-06-11 10:56:38'),
(4, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-06-11 12:56:52', '2026-06-11 12:56:52', 0, '2026-06-11 10:56:52'),
(5, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'تعديل رسالة المستخدمين أثناء الصيانة', '2026-06-11 13:10:46', '2026-06-11 13:10:46', 0, '2026-06-11 11:10:46'),
(6, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'تعديل رسالة المستخدمين أثناء الصيانة', '2026-06-11 13:10:47', '2026-06-11 13:10:47', 0, '2026-06-11 11:10:47'),
(7, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'تعديل رسالة المستخدمين أثناء الصيانة', '2026-06-11 13:10:47', '2026-06-11 13:10:47', 0, '2026-06-11 11:10:47'),
(8, 96, 'nadjet kheira', 'super_admin', 'disable', 'other', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-06-11 13:11:03', '2026-06-11 13:11:03', 0, '2026-06-11 11:11:03'),
(9, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:34:58', '2026-07-05 20:34:58', 0, '2026-07-05 18:34:58'),
(10, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:35:00', '2026-07-05 20:39:06', 4, '2026-07-05 18:35:00'),
(11, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-07-05 20:35:20', '2026-07-05 20:35:20', 0, '2026-07-05 18:35:20'),
(12, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-07-05 20:37:32', '2026-07-05 20:37:32', 0, '2026-07-05 18:37:32'),
(13, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-07-05 20:38:00', '2026-07-05 20:38:00', 0, '2026-07-05 18:38:00'),
(14, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:39:59', '2026-07-05 20:39:59', 0, '2026-07-05 18:39:59'),
(15, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:40:01', '2026-07-05 20:40:38', 1, '2026-07-05 18:40:01'),
(16, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-07-05 20:40:06', '2026-07-05 20:40:06', 0, '2026-07-05 18:40:06'),
(17, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:41:43', '2026-07-05 20:41:43', 0, '2026-07-05 18:41:43'),
(18, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'fjuhnblhn', '2026-07-05 20:41:44', '2026-07-05 20:46:15', 5, '2026-07-05 18:41:44'),
(19, 96, 'nadjet kheira', 'super_admin', 'disable', 'security_update', 'تعديل صلاحيات الوصول أثناء الصيانة', '2026-07-05 20:41:49', '2026-07-05 20:41:49', 0, '2026-07-05 18:41:49');

-- --------------------------------------------------------

--
-- Structure de la table `maintenance_settings`
--

CREATE TABLE `maintenance_settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `maintenance_settings`
--

INSERT INTO `maintenance_settings` (`key`, `value`, `updated_at`) VALUES
('access_clinics', '0', '2026-07-05 18:37:32'),
('access_doctors', '0', '2026-07-05 18:37:32'),
('access_hospitals', '0', '2026-07-05 18:37:32'),
('access_institutions', '0', '2026-06-11 10:26:33'),
('access_labs', '0', '2026-07-05 18:37:32'),
('access_patients', '0', '2026-07-05 18:37:32'),
('access_pharmacies', '0', '2026-07-05 18:37:32'),
('ended_at', '2026-07-05 20:46:15', '2026-07-05 18:46:15'),
('end_date', '2026-07-13T04:04', '2026-06-11 10:26:18'),
('is_on', '0', '2026-07-05 18:46:15'),
('maint_type', 'security_update', '2026-07-05 18:34:58'),
('reason', 'fjuhnblhn', '2026-06-11 10:26:18'),
('started_at', '2026-07-05 20:41:44', '2026-07-05 18:41:44'),
('start_date', '2026-07-05T20:36', '2026-07-05 18:34:58'),
('user_message', 'منصة MedChifaGiz تخضع حالياً لأعمال صيانة وتحسين للخدمات الصحية الرقمية. نعتذر عن الإزعاج ونشكركم على تفهمكم.', '2026-06-11 10:26:27');

-- --------------------------------------------------------

--
-- Structure de la table `medical_followups`
--

CREATE TABLE `medical_followups` (
  `id` int(11) NOT NULL,
  `medical_record_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `followup_date` date NOT NULL,
  `new_symptoms` text DEFAULT NULL,
  `new_treatment` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medical_followups`
--

INSERT INTO `medical_followups` (`id`, `medical_record_id`, `doctor_id`, `followup_date`, `new_symptoms`, `new_treatment`, `doctor_notes`, `created_at`) VALUES
(3, 33, 50, '2026-05-31', 'jhy', 'njh', 'uh', '2026-05-31 13:36:40'),
(4, 63, 50, '2026-07-08', 'bgea', NULL, NULL, '2026-07-01 00:55:40');

-- --------------------------------------------------------

--
-- Structure de la table `medical_history_log`
--

CREATE TABLE `medical_history_log` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `field_type` varchar(60) NOT NULL,
  `field_key` varchar(60) NOT NULL,
  `added_text` text NOT NULL,
  `added_by` varchar(120) DEFAULT '',
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medical_history_log`
--

INSERT INTO `medical_history_log` (`id`, `record_id`, `field_type`, `field_key`, `added_text`, `added_by`, `added_at`) VALUES
(1, 42, 'fiche_traitement', 'fiche_medications', 'epoprofine', '', '2026-06-01 22:18:13');

-- --------------------------------------------------------

--
-- Structure de la table `medical_messages`
--

CREATE TABLE `medical_messages` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_user_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `sender_role` enum('doctor','patient') NOT NULL,
  `message` text NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(100) DEFAULT NULL,
  `voice_path` varchar(255) DEFAULT NULL,
  `voice_duration` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_edited` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `reply_to_message_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medical_messages`
--

INSERT INTO `medical_messages` (`id`, `record_id`, `doctor_id`, `patient_user_id`, `sender_id`, `receiver_id`, `sender_role`, `message`, `attachment_path`, `attachment_name`, `attachment_type`, `voice_path`, `voice_duration`, `is_read`, `created_at`, `is_deleted`, `is_edited`, `is_pinned`, `reply_to_message_id`) VALUES
(1, 69, 80, 188, 80, 188, 'doctor', 'slm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-01 23:06:53', 0, 0, 0, NULL),
(2, 69, 80, 188, 80, 188, 'doctor', 'cv', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-01 23:07:05', 0, 0, 0, NULL),
(3, 69, 80, 188, 80, 188, 'doctor', 'cg', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:01:14', 0, 0, 0, NULL),
(4, 69, 50, 188, 188, 50, 'patient', 'gg', NULL, NULL, NULL, NULL, NULL, 0, '2026-07-03 03:02:08', 0, 0, 0, NULL),
(5, 69, 80, 188, 188, 80, 'patient', 'ff', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:03:56', 0, 0, 0, NULL),
(6, 69, 80, 188, 188, 80, 'patient', 'ss', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:04:03', 0, 0, 0, NULL),
(7, 69, 80, 188, 188, 80, 'patient', 'rr', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:04:10', 0, 0, 0, NULL),
(8, 69, 80, 188, 188, 80, 'patient', 'qq', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:04:18', 0, 0, 0, NULL),
(9, 69, 80, 188, 80, 188, 'doctor', 'qq', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:15:36', 0, 0, 0, NULL),
(10, 69, 80, 188, 80, 188, 'doctor', 's', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:15:46', 0, 0, 0, NULL),
(11, 69, 80, 188, 188, 80, 'patient', 'r', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:16:14', 0, 0, 0, NULL),
(12, 69, 80, 188, 188, 80, 'patient', 'q', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:16:21', 0, 0, 0, NULL),
(13, 69, 80, 188, 188, 80, 'patient', 'n', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:16:26', 0, 0, 0, NULL),
(14, 69, 80, 188, 80, 188, 'doctor', 'm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:21:33', 0, 0, 0, NULL),
(15, 69, 80, 188, 188, 80, 'patient', 't', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:22:11', 0, 0, 0, NULL),
(16, 69, 80, 188, 188, 80, 'patient', 'r', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:22:16', 0, 0, 0, NULL),
(17, 69, 80, 188, 188, 80, 'patient', 'q', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:22:22', 0, 0, 0, NULL),
(18, 69, 80, 188, 188, 80, 'patient', 'q', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:22:30', 0, 0, 0, NULL),
(19, 69, 80, 188, 188, 80, 'patient', 'm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:22:36', 0, 0, 0, NULL),
(20, 69, 80, 188, 80, 188, 'doctor', 'h', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:33:56', 0, 0, 0, NULL),
(21, 69, 80, 188, 80, 188, 'doctor', 'n', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:34:00', 0, 0, 0, NULL),
(22, 69, 80, 188, 188, 80, 'patient', 'jj', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:34:25', 0, 0, 0, NULL),
(23, 69, 80, 188, 188, 80, 'patient', 'mm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:34:31', 0, 0, 0, NULL),
(24, 69, 80, 188, 188, 80, 'patient', 'mm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:34:34', 0, 0, 0, NULL),
(25, 69, 80, 188, 188, 80, 'patient', 'ى', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:43:39', 0, 0, 0, NULL),
(26, 69, 80, 188, 188, 80, 'patient', 'ى', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:43:46', 0, 0, 0, NULL),
(27, 69, 80, 188, 188, 80, 'patient', 'ت', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:43:53', 0, 0, 0, NULL),
(28, 69, 80, 188, 188, 80, 'patient', 'لالا', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:44:09', 0, 0, 0, NULL),
(29, 69, 80, 188, 188, 80, 'patient', 'لالا', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:44:15', 0, 0, 0, NULL),
(30, 69, 80, 188, 188, 80, 'patient', 'ىى', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:44:21', 0, 0, 0, NULL),
(31, 69, 80, 188, 188, 80, 'patient', 'سشص', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:48:26', 0, 0, 0, NULL),
(32, 69, 80, 188, 80, 188, 'doctor', 'ji', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:50:29', 0, 0, 0, NULL),
(33, 69, 80, 188, 188, 80, 'patient', 'jo', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:50:49', 0, 0, 0, NULL),
(34, 69, 80, 188, 188, 80, 'patient', 'ju', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:50:55', 0, 0, 0, NULL),
(35, 69, 80, 188, 188, 80, 'patient', 'ww', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 03:51:13', 0, 0, 0, NULL),
(36, 69, 80, 188, 80, 188, 'doctor', 'yo', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:29:35', 0, 0, 0, NULL),
(37, 69, 80, 188, 188, 80, 'patient', 'io', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:30:44', 0, 0, 0, NULL),
(38, 69, 80, 188, 80, 188, 'doctor', 'hj', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:30:54', 0, 0, 0, NULL),
(39, 69, 80, 188, 188, 80, 'patient', 'op', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:31:00', 0, 0, 0, NULL),
(40, 69, 80, 188, 80, 188, 'doctor', 'op', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:31:07', 0, 0, 0, NULL),
(41, 69, 80, 188, 188, 80, 'patient', 'yt', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:31:12', 0, 0, 0, NULL),
(42, 69, 80, 188, 188, 80, 'patient', '😊', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:40:51', 0, 0, 0, NULL),
(43, 69, 80, 188, 80, 188, 'doctor', 'ino', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:45:56', 0, 0, 0, NULL),
(44, 69, 80, 188, 188, 80, 'patient', 'oui', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:46:11', 0, 0, 0, NULL),
(45, 69, 80, 188, 188, 80, 'patient', 'hi', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:56:35', 0, 0, 0, NULL),
(46, 69, 80, 188, 80, 188, 'doctor', 'cv', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:56:58', 0, 0, 0, NULL),
(47, 69, 80, 188, 188, 80, 'patient', 'hmdlh', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:57:05', 0, 0, 0, NULL),
(48, 69, 80, 188, 80, 188, 'doctor', 'b1', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 13:57:13', 0, 0, 0, NULL),
(49, 69, 80, 188, 80, 188, 'doctor', '', 'uploads/chat_files/20260703_152429_d58d69527e99.png', 'medchifa_logo.png', 'image/png', NULL, NULL, 1, '2026-07-03 15:24:29', 0, 0, 0, NULL),
(50, 69, 80, 188, 188, 80, 'patient', '', 'uploads/chat_files/20260703_152456_27aa3c9ccaf3.png', 'medchifagz.png', 'image/png', NULL, NULL, 1, '2026-07-03 15:24:56', 0, 0, 0, NULL),
(51, 69, 80, 188, 80, 188, 'doctor', 'hi', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:01:34', 0, 0, 0, NULL),
(52, 69, 80, 188, 188, 80, 'patient', 'oui', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:01:52', 0, 0, 0, NULL),
(53, 69, 80, 188, 80, 188, 'doctor', 'n', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:02:02', 0, 1, 0, NULL),
(54, 69, 80, 188, 80, 188, 'doctor', 'n', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:02:27', 0, 0, 0, NULL),
(55, 69, 80, 188, 188, 80, 'patient', 'b', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:03:13', 0, 0, 0, NULL),
(56, 69, 80, 188, 188, 80, 'patient', 's', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:06:45', 0, 0, 0, NULL),
(57, 69, 80, 188, 188, 80, 'patient', '..', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:19:28', 0, 0, 0, NULL),
(58, 69, 80, 188, 80, 188, 'doctor', '..', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 16:19:37', 0, 0, 0, NULL),
(59, 69, 80, 188, 80, 188, 'doctor', 'slm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 18:00:53', 0, 0, 0, NULL),
(60, 69, 80, 188, 80, 188, 'doctor', '', 'uploads/chat_files/20260703_180111_2d1d827fbab4.png', 'Screenshot (1).png', 'image/png', NULL, NULL, 1, '2026-07-03 18:01:11', 0, 0, 0, NULL),
(61, 69, 80, 188, 188, 80, 'patient', 'hell', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-03 18:02:16', 0, 1, 0, NULL),
(62, 69, 80, 188, 80, 188, 'doctor', '', NULL, NULL, NULL, 'uploads/chat_voice/voice_20260703_204236_5f5864442f98.webm', 14, 1, '2026-07-03 20:42:36', 1, 0, 0, NULL),
(63, 69, 80, 188, 80, 188, 'doctor', '', 'view_medical_document.php?type=dossier&record_id=69', 'Dossier Médical.html', 'medical/document', NULL, NULL, 1, '2026-07-03 21:25:01', 1, 0, 0, NULL),
(64, 69, 80, 188, 188, 80, 'patient', 'hjk', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-04 02:46:05', 1, 0, 0, NULL),
(65, 69, 80, 188, 80, 188, 'doctor', 'lilo', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-04 02:46:50', 0, 0, 0, NULL),
(66, 69, 80, 188, 80, 188, 'doctor', 'slm', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-04 09:47:20', 0, 0, 0, 59),
(67, 69, 80, 188, 188, 80, 'patient', '', NULL, NULL, NULL, 'uploads/chat_voice/voice_20260704_095027_8903f0261628.webm', 9, 1, '2026-07-04 09:50:27', 1, 0, 0, NULL),
(68, 69, 80, 188, 188, 80, 'patient', '', NULL, NULL, NULL, 'uploads/chat_voice/voice_20260704_114746_4dc6ae2225c5.webm', 6, 1, '2026-07-04 11:47:46', 0, 0, 0, NULL),
(69, 69, 80, 188, 188, 80, 'patient', 'nakatokkk', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-04 15:30:46', 0, 1, 0, NULL),
(70, 69, 80, 188, 188, 80, 'patient', 'jjbb', NULL, NULL, NULL, NULL, NULL, 1, '2026-07-04 15:46:45', 0, 1, 0, 69);

-- --------------------------------------------------------

--
-- Structure de la table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `birth_info` varchar(255) DEFAULT NULL,
  `marital_status` varchar(100) DEFAULT NULL,
  `job` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reason_exam` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `blood_sugar` varchar(50) DEFAULT NULL,
  `heart_rate` varchar(50) DEFAULT NULL,
  `temperature` varchar(50) DEFAULT NULL,
  `oxygen_level` varchar(50) DEFAULT NULL,
  `chronic_patient` text DEFAULT NULL,
  `chronic_family` text DEFAULT NULL,
  `medical_tests` text DEFAULT NULL,
  `radiology` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `next_appointment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `next_appointment_date` date DEFAULT NULL,
  `next_appointment_time` time DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `residency_status` varchar(50) DEFAULT NULL,
  `last_period_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `preg_blood_type` varchar(20) DEFAULT NULL,
  `pregnancies_count` int(11) DEFAULT NULL,
  `births_count` int(11) DEFAULT NULL,
  `miscarriages_count` int(11) DEFAULT NULL,
  `c_sections_count` int(11) DEFAULT NULL,
  `preg_chronic_diseases` text DEFAULT NULL,
  `father_status` varchar(100) DEFAULT NULL,
  `consanguinity` varchar(100) DEFAULT NULL,
  `pregnancy_notes` text DEFAULT NULL,
  `preg_weight` varchar(50) DEFAULT NULL,
  `preg_blood_pressure` varchar(50) DEFAULT NULL,
  `preg_sugar_level` varchar(50) DEFAULT NULL,
  `fetal_heartbeat` varchar(50) DEFAULT NULL,
  `fetal_movement` varchar(50) DEFAULT NULL,
  `fetal_weight` varchar(50) DEFAULT NULL,
  `fetal_position` varchar(100) DEFAULT NULL,
  `echo_notes` text DEFAULT NULL,
  `followup_notes` text DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `medical_records`
--

INSERT INTO `medical_records` (`id`, `patient_id`, `doctor_id`, `full_name`, `birth_info`, `marital_status`, `job`, `address`, `phone`, `email`, `reason_exam`, `symptoms`, `blood_pressure`, `blood_sugar`, `heart_rate`, `temperature`, `oxygen_level`, `chronic_patient`, `chronic_family`, `medical_tests`, `radiology`, `prescription`, `next_appointment`, `created_at`, `updated_at`, `next_appointment_date`, `next_appointment_time`, `admission_date`, `residency_status`, `last_period_date`, `expected_delivery_date`, `preg_blood_type`, `pregnancies_count`, `births_count`, `miscarriages_count`, `c_sections_count`, `preg_chronic_diseases`, `father_status`, `consanguinity`, `pregnancy_notes`, `preg_weight`, `preg_blood_pressure`, `preg_sugar_level`, `fetal_heartbeat`, `fetal_movement`, `fetal_weight`, `fetal_position`, `echo_notes`, `followup_notes`, `gender`) VALUES
(33, 0, 50, 'حليمة', '', '', '', '', '', NULL, 'htrww4', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-05-29 20:44:28', '2026-05-29 20:44:28', NULL, NULL, NULL, 'مقيم', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', 'no', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(42, 0, 50, 'Bachir', '10/05/1967', 'mariee', 'retraiter', 'حي 60 مسكن الجزائر', '04567892345', NULL, 'ألم حاد في الركبة بعد سقوط، يطلب تصوير لتقييم الإصابة أو وجود كسر.\r\n[01/06/2026] mnjhg\r\n[01/06/2026] kkkkkk\r\n[01/06/2026] ', 'ألم مفصل مع تورم وصعوبة في الحركة.\r\n[01/06/2026] kjyftf\r\n[01/06/2026]\r\n[01/06/2026] ', '80\r\n[01/06/2026] 90', '67\r\n[01/06/2026] ', '87\r\n[01/06/2026] ', '37\r\n[01/06/2026] ', '48\r\n[01/06/2026] ', 'السكري والضغط\r\n[01/06/2026] mnhyfd\r\n[01/06/2026]\r\n[01/06/2026] ', 'لايوجد\r\n[01/06/2026] mnhg\r\n[01/06/2026]\r\n[01/06/2026] ', 'NFS\r\n[01/06/2026] mjjh\r\n[01/06/2026]\r\n[01/06/2026] ', 'ECHO ABDOMINALE\r\n[01/06/2026]\r\n[01/06/2026] ', 'Amoxicilline 1g\r\n1 comprimé matin et soir – 7 jours\r\n[01/06/2026]\r\n[01/06/2026] ', 'yes', '2026-05-30 18:41:06', '2026-06-01 20:44:41', '2026-06-16', '05:08:00', '2026-05-30', 'مقيم', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', 'no', '', '', '', '', '', '', '', '', '', '', 'ذكر'),
(43, 0, 50, 'najet', '', '', '', '', '', NULL, 'dfhuowvou', '', '', '', '', '', '', '', '', 'NFS', '', '', 'no', '2026-06-24 19:44:00', '2026-06-24 19:44:00', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(44, 0, 50, 'najet', '', '', '', '', '', NULL, 'ckjPIE', '', '', '', '', '', '', '', '', '', '', 'paracetamol\r\nheptajil\r\ndoliprane', 'no', '2026-06-24 21:03:34', '2026-06-24 21:03:34', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(45, 0, 50, 'lamia', '', '', '', '', '', NULL, 'mkiolp', '', '', '', '', '', '', '', '', 'crp', 'rx', 'mjhtfcvn', 'no', '2026-06-25 11:10:08', '2026-06-25 11:10:08', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(46, 0, 50, 'saffae', '', '', '', '', '', NULL, ',kutfvh', '', '', '', '', '', '', '', '', 'NFS CRP', 'RX ECHO ABDOMINAUX', 'PARACETAMOL', 'no', '2026-06-25 11:31:35', '2026-06-25 11:31:35', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(47, 0, 50, 'mahmoud', '', '', '', '', '', NULL, 'dkjwoig', '', '', '', '', '', '', '', '', 'nfs', 'rx', 'prctmol', 'no', '2026-06-25 11:48:18', '2026-06-25 11:48:18', NULL, NULL, NULL, 'مقيم', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'ذكر'),
(48, 0, 50, 'lilo', '', '', '', '', '', NULL, 'jc/iSGHER', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 11:50:36', '2026-06-25 11:50:36', NULL, NULL, '2026-06-25', '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(49, 0, 50, 'hayk', '', '', '', '', '', NULL, 'ckdwjgpi', '', '', '', '', '', '', '', '', '', '', '', 'لا', '2026-06-25 12:21:33', '2026-06-27 19:38:48', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'ذكر'),
(50, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 14:39:44', '2026-06-25 14:39:44', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(51, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 15:06:04', '2026-06-25 15:06:04', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(52, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 15:06:20', '2026-06-25 15:06:20', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(53, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 18:51:37', '2026-06-25 18:51:37', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(54, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-25 18:55:07', '2026-06-25 18:55:07', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', NULL),
(55, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 11:54:02', '2026-06-26 11:54:02', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(56, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 11:56:29', '2026-06-26 11:56:29', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(57, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 12:43:27', '2026-06-26 12:43:27', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(58, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 12:56:12', '2026-06-26 12:56:12', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(59, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 12:59:06', '2026-06-26 12:59:06', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(60, 0, 50, '', '', '', '', '', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-26 17:11:35', '2026-06-26 17:11:35', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', ''),
(61, 185, 50, 'chikh', '', '', '', '', '', NULL, 'm3lblnch', '', '', '', '', '', '', '', '', 'm3lbliiiiiiiiiiiiiiiiiiiiiiiich', 'waaaaaaaaaaaaa', 'm3lbliiiiiiiiiiiiiiiiiiiich', 'no', '2026-06-26 19:32:13', '2026-06-26 19:52:22', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'ذكر'),
(62, 182, 50, 'robin', '', '', '', '', '', NULL, 'hay', '', '', '', '', '', '', '', '', 'hay', 'hay', 'hay', 'no', '2026-06-26 19:36:53', '2026-06-26 19:36:53', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(63, 0, 50, 'rahaf', '', '', '', '', '', NULL, 'm3lblich', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-06-27 16:12:48', '2026-06-27 16:12:48', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(64, 0, 50, 'tp', '', '', '', '', '', NULL, 'jl/ug', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 17:48:35', '2026-07-01 17:48:35', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'ذكر'),
(65, 182, 50, 'tppaw', '', '', '', '', '', NULL, 'cdw;kv', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 18:01:34', '2026-07-01 18:01:34', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(66, 186, 50, 'jamila', '', '', '', '', '', 'nadjetjiji703@gmail.com', 'dv.nj', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 19:27:13', '2026-07-01 20:31:30', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(67, 0, 50, 'khyrour', '', '', '', '', '', 'djefalkheira@gmail.com', 'ndcbvqj', 'lf', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 19:55:08', '2026-07-01 19:55:08', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(68, 187, 50, 'hakemnadjet', '', '', '', '', '', 'hakemnadjet11@gmail.com', 'hh', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 20:34:31', '2026-07-01 20:35:32', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(69, 188, 50, 'inomina', '', '', '', '', '', 'hakemnadjet11@gmail.com', 'vdk', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-01 20:50:01', '2026-07-01 20:51:14', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'أنثى'),
(70, 0, 50, 'oiy', '', '', '', '', '', '', 'ljh', '', '', '', '', '', '', '', '', '', '', '', 'no', '2026-07-05 23:00:36', '2026-07-05 23:00:36', NULL, NULL, NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', 'ذكر');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 76, '📅 تم تحديد موعدك يوم 2026-02-02 على الساعة 02:00', 0, '2026-04-19 22:58:46'),
(2, 76, '📅 تم تحديد موعدك يوم 0111-01-11 على الساعة 00:00', 0, '2026-04-19 23:04:49'),
(3, 76, '📅 موعد مع د.  \r\n🗓️ يوم 2026-04-19 \r\n🕐 على الساعة 03:00', 0, '2026-04-19 23:11:14'),
(4, 76, '📅 موعد مع د.  \r\n🗓️ يوم 2026-05-06 \r\n🕐 على الساعة 02:03', 0, '2026-04-19 23:11:46'),
(5, 76, '📅 موعد مع د. kheira \r\n🗓️ يوم 2026-05-06 \r\n🕐 على الساعة 04:00', 0, '2026-04-19 23:16:41'),
(6, 76, '📅 موعد مع د. kheira \r\n🗓️ يوم 2026-04-20 \r\n🕐 على الساعة 08:00', 0, '2026-04-19 23:18:47'),
(7, 76, '📅 موعد طبي\r\n🩺 مع د. kheira\r\n📅 يوم 2026-04-19\r\n⏰ على الساعة 00:00', 0, '2026-04-19 23:24:19'),
(8, 76, '📅 موعد طبي\r\n🩺 مع د. kheira\r\n📅 يوم 2026-05-06\r\n⏰ على الساعة 04:44', 0, '2026-04-19 23:25:04'),
(9, 76, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-05-06 \r\n🕐 على الساعة 03:03', 0, '2026-04-19 23:26:23'),
(10, 77, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-02-02 \r\n🕐 على الساعة 22:02', 0, '2026-04-19 23:32:27'),
(11, 76, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-04-04 \r\n🕐 على الساعة 04:04', 0, '2026-04-19 23:57:13'),
(12, 77, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-03-03 \r\n🕐 على الساعة 22:02', 0, '2026-04-20 00:04:29'),
(13, 62, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-07-07 \r\n🕐 على الساعة 09:00', 0, '2026-04-20 12:32:22'),
(14, 76, '  🩺 موعد مع د. kheira \r\n🗓️ يوم 2026-09-09 \r\n🕐 على الساعة 08:00', 0, '2026-04-20 12:34:12'),
(15, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/07/2026\n🕐 على الساعة 08:00', 0, '2026-05-06 22:49:14'),
(16, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 11/11/1111\n🕐 على الساعة 11:11', 0, '2026-05-06 23:12:12'),
(17, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 03/03/3333\n🕐 على الساعة 03:03', 0, '2026-05-06 23:24:02'),
(18, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 01/09/0099\n🕐 على الساعة 00:00', 0, '2026-05-06 23:48:18'),
(19, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 11/11/2026\n🕐 على الساعة 12:00', 0, '2026-05-07 13:02:11'),
(20, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 19:00', 0, '2026-05-07 14:45:30'),
(21, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 08/06/2026\n🕐 على الساعة 20:00', 0, '2026-05-07 14:51:10'),
(22, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 09:00', 0, '2026-05-07 14:52:44'),
(23, 86, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 08/05/2026\n🕐 على الساعة 09:00', 0, '2026-05-07 15:28:16'),
(24, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 09:00', 0, '2026-05-07 15:45:33'),
(25, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-07 16:05:57'),
(26, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 04:00', 0, '2026-05-07 16:12:49'),
(27, 76, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-05-08 على الساعة 04:04', 0, '2026-05-07 16:13:21'),
(28, 76, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-05-05 على الساعة 05:05', 0, '2026-05-07 16:26:12'),
(29, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 07/05/2026\n🕐 على الساعة 04:04', 0, '2026-05-07 16:31:18'),
(30, 76, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-10-05 على الساعة 04:04', 0, '2026-05-07 16:31:51'),
(31, 76, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-08-08 على الساعة 04:04', 0, '2026-05-07 16:32:36'),
(32, 76, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-05-07 على الساعة 08:08', 0, '2026-05-07 16:35:16'),
(33, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 09/05/2026\n🕐 على الساعة 10:00', 0, '2026-05-09 14:34:33'),
(34, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-09 23:27:41'),
(35, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-09 23:29:56'),
(36, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-09 23:37:04'),
(37, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-09 23:43:17'),
(38, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-09 23:46:11'),
(39, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 03/06/2026\n🕐 على الساعة 22:02', 0, '2026-05-10 01:14:45'),
(40, 80, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-05-10 على الساعة 22:02', 0, '2026-05-10 01:15:10'),
(41, 62, '📅 تم تأكيد موعدك\n🩺 مع د. nadjet\n🗓️ يوم 10/05/2026\n🕐 على الساعة 08:00', 0, '2026-05-10 11:28:51'),
(42, 87, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 07:07', 0, '2026-05-10 11:31:04'),
(43, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 10/05/2026\n🕐 على الساعة 08:08', 0, '2026-05-10 11:33:15'),
(44, 0, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-05-11 على الساعة 22:02', 0, '2026-05-10 22:57:46'),
(45, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 11/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-10 22:59:11'),
(46, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 12/05/2026\n🕐 على الساعة 22:02', 0, '2026-05-11 23:00:24'),
(47, 86, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 12/05/2026\n🕐 على الساعة 08:08', 0, '2026-05-11 23:28:14'),
(48, 87, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 12/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-12 11:59:17'),
(49, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 14/05/2026\n🕐 على الساعة 09:09', 0, '2026-05-14 09:38:33'),
(50, 80, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 16/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-16 00:05:54'),
(51, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 16/05/2026\n🕐 على الساعة 07:07', 0, '2026-05-16 00:20:57'),
(52, 87, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 16/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-16 00:48:46'),
(53, 87, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 16/05/2026\n🕐 على الساعة 08:08', 0, '2026-05-16 00:49:37'),
(54, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 17/05/2026\n🕐 على الساعة 09:09', 0, '2026-05-16 23:05:25'),
(55, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 17/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-17 12:11:29'),
(56, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 18/05/2026\n🕐 على الساعة 04:04', 0, '2026-05-17 22:52:34'),
(57, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 19/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-18 22:05:23'),
(58, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 19/05/2026\n🕐 على الساعة 22:22', 0, '2026-05-19 07:03:41'),
(59, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 20/05/2026\n🕐 على الساعة 08:08', 0, '2026-05-20 16:28:48'),
(60, 76, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 21/05/2026\n🕐 على الساعة 04:04', 0, '2026-05-20 23:57:56'),
(61, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 21/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-21 05:43:32'),
(62, 62, '📅 تم تأكيد موعدك\n🩺 مع د. kheira\n🗓️ يوم 22/05/2026\n🕐 على الساعة 04:04', 0, '2026-05-22 15:01:39'),
(63, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 23/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-23 21:57:29'),
(64, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 24/05/2026\n🕐 على الساعة 07:07', 0, '2026-05-24 09:58:32'),
(65, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 25/05/2026\n🕐 على الساعة 04:04', 0, '2026-05-25 20:04:33'),
(66, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 29/05/2026\n🕐 على الساعة 03:04', 0, '2026-05-29 20:47:02'),
(67, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 30/05/2026\n🕐 على الساعة 07:07', 0, '2026-05-30 14:49:02'),
(68, 62, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 31/05/2026\n🕐 على الساعة 03:03', 0, '2026-05-31 12:17:13'),
(69, 80, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 25/06/2026\n🕐 على الساعة 10:00', 0, '2026-06-25 11:06:12'),
(70, 184, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 25/06/2026\n🕐 على الساعة 02:02', 0, '2026-06-25 13:52:28'),
(71, 185, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 25/06/2026\n🕐 على الساعة 04:04', 0, '2026-06-25 15:04:37'),
(72, 182, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 03:03', 0, '2026-06-26 11:49:01'),
(73, 185, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 22:02', 0, '2026-06-26 12:58:36'),
(74, 185, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 23:00', 0, '2026-06-26 17:11:01'),
(75, 185, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 22:02', 0, '2026-06-26 19:31:45'),
(76, 182, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 22:22', 0, '2026-06-26 19:35:52'),
(77, 182, '🔄 تم إعادة برمجة موعدك إلى يوم 2026-06-26 على الساعة 22:22', 0, '2026-06-26 19:39:46'),
(78, 185, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 26/06/2026\n🕐 على الساعة 02:03', 0, '2026-06-26 19:51:00'),
(79, 188, '📅 تم تأكيد موعدك\n🩺 مع د. bennourcheikh\n🗓️ يوم 05/07/2026\n🕐 على الساعة 01:01', 0, '2026-07-04 07:57:47');

-- --------------------------------------------------------

--
-- Structure de la table `nurses`
--

CREATE TABLE `nurses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `nurses`
--

INSERT INTO `nurses` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`) VALUES
(1, 'Infirmier Ahmed', 35.1888, -0.632, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(2, 'Infirmière Salma', 35.191, -0.636, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(3, 'Infirmier Amarnas', 35.154, -0.701, 'Sidi Bel Abbes', 'Amarnas'),
(4, 'Infirmière Sidi Lahcene', 35.2042, -0.6855, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(5, 'Soins à Domicile Nour', 35.1875, -0.6275, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(6, 'ممرض محمد بن علي', 35.1901, -0.6301, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(7, 'ممرضة فاطمة الزهراء', 35.192, -0.635, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(8, 'ممرض أحمد قاسمي', 35.185, -0.62, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(9, 'ممرضة سمية بوعلام', 35.188, -0.64, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(10, 'ممرض عبد القادر', 35.195, -0.645, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(11, 'ممرض إلياس عمار', 35.15, -0.7, 'Sidi Bel Abbes', 'Amarnas'),
(12, 'ممرضة نوال زروقي', 35.152, -0.705, 'Sidi Bel Abbes', 'Amarnas'),
(13, 'ممرض حسين شارف', 35.205, -0.69, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(14, 'ممرضة هاجر بن سالم', 35.207, -0.695, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(15, 'ممرض رشيد بوعزة', 35.17, -0.66, 'Sidi Bel Abbes', 'Sfisef'),
(16, 'ممرضة خديجة بن يحي', 35.175, -0.665, 'Sidi Bel Abbes', 'Sfisef'),
(17, 'ممرض نور الدين', 35.18, -0.625, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(18, 'ممرضة أمينة قادري', 35.182, -0.628, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(19, 'ممرض يوسف مهدي', 35.184, -0.632, 'Sidi Bel Abbes', 'Sidi Bel Abbes');

-- --------------------------------------------------------

--
-- Structure de la table `nurse_treatments`
--

CREATE TABLE `nurse_treatments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `birth_info` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `aile` varchar(20) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `motif` text DEFAULT NULL,
  `diagnostic` text DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `treatments` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `nurse_treatments`
--

INSERT INTO `nurse_treatments` (`id`, `patient_id`, `patient_name`, `birth_info`, `gender`, `room`, `service`, `aile`, `doctor_id`, `doctor_name`, `motif`, `diagnostic`, `admission_date`, `treatments`, `status`, `created_at`, `updated_at`) VALUES
(1, 44, 'najet', '', 'أنثى', '', 'Service de Médecine Interne', 'women', 50, '', '', 'mardi', NULL, '[{\"name\":\"paracetamol 3 fois\",\"medicament\":\"paracetamol 3 fois\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-24 21:03:57', '2026-06-24 21:06:30'),
(3, 46, 'saffae', '', 'أنثى', '', 'Service de Médecine Interne', 'women', 50, '', ',kutfvh', 'NCURLVF', NULL, '[{\"name\":\"PARACETAMOL IPOPROFINE DOLIPRANE\",\"medicament\":\"PARACETAMOL IPOPROFINE DOLIPRANE\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-25 11:31:35', '2026-06-25 11:31:35'),
(4, 47, 'mahmoud', '', '', '', 'Service de Médecine Interne', '', 50, '', 'dkjwoig', 'vkjv\'pfw', NULL, '[{\"name\":\"prctmol\",\"medicament\":\"prctmol\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-25 11:48:18', '2026-06-25 11:48:18'),
(5, 53, 'ahmed', '', '', '', 'Service de Médecine Interne', '', 50, '', 'mgjtrgh', 'nchlh ykhdem', NULL, '[{\"name\":\"nchlh\",\"medicament\":\"nchlh\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-25 18:51:48', '2026-06-25 18:51:48'),
(6, 55, 'karim', '', 'ذكر', '', 'Service de Médecine Interne', 'men', 50, '', 'mhcyitfi', 'ya rabi ykhdem lyoum', NULL, '[{\"name\":\"kdwj\\/i;r\",\"medicament\":\"kdwj\\/i;r\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-26 11:54:24', '2026-06-26 11:54:24'),
(7, 57, 'najato', '', 'أنثى', '', 'Service de Médecine Interne', 'women', 50, '', 'la a3lam', 'claude ai', NULL, '[{\"name\":\"1234\",\"medicament\":\"1234\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-26 12:43:27', '2026-06-26 12:43:27'),
(8, 61, 'chikh', '', 'ذكر', '', 'Service de Médecine Interne', 'men', 50, '', 'm3lblnch', 'm3lbliiiiiiiiiich', NULL, '[{\"name\":\"m3lblich\",\"medicament\":\"m3lblich\",\"dose\":\"\",\"heure\":\"\",\"freq\":\"\",\"duree\":\"\",\"instructions\":\"\"}]', 'pending', '2026-06-26 19:51:58', '2026-06-26 19:51:58');

-- --------------------------------------------------------

--
-- Structure de la table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `chronic_diseases` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `health_notes` text DEFAULT NULL,
  `emergency_name` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `medical_completed` tinyint(1) DEFAULT 0,
  `emergency_token` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `residency_status` varchar(50) DEFAULT NULL,
  `last_period_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `doctor_id`, `age`, `gender`, `blood_type`, `created_at`, `first_name`, `last_name`, `birth_date`, `height`, `weight`, `phone`, `chronic_diseases`, `allergies`, `medications`, `health_notes`, `emergency_name`, `emergency_phone`, `medical_completed`, `emergency_token`, `admission_date`, `residency_status`, `last_period_date`) VALUES
(19, 79, NULL, NULL, NULL, NULL, '2026-04-30 11:23:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(29, 96, NULL, NULL, NULL, NULL, '2026-06-05 18:49:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(36, 188, NULL, NULL, '', '', '2026-07-01 20:51:14', '', '', '0000-00-00', '', '', '', '', '', '', '', '', '', 1, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `pharmacies`
--

CREATE TABLE `pharmacies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pharmacies`
--

INSERT INTO `pharmacies` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`) VALUES
(1, 'Pharmacie El Amal', 35.1891, -0.6305, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(2, 'Pharmacie Ibn Sina', 35.1912, -0.635, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(3, 'Pharmacie Amarnas Centre', 35.153, -0.702, 'Sidi Bel Abbes', 'Amarnas'),
(4, 'Pharmacie Sidi Lahcene 1', 35.2035, -0.684, 'Sidi Bel Abbes', 'Sidi Lahcene'),
(5, 'Pharmacie El Chifa', 35.187, -0.628, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(6, 'Pharmacie El Nour', 35.189, -0.63, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(7, 'Pharmacie El Chifa 2', 35.195, -0.64, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(8, 'Pharmacie Ibn Rochd', 35.2, -0.62, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(9, 'Pharmacie El Baraka', 35.18, -0.615, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(10, 'Pharmacie El Amal 2', 35.178, -0.625, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(11, 'Pharmacie El Hikma', 35.182, -0.635, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(12, 'Pharmacie Centrale SBA', 35.188, -0.645, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(13, 'Pharmacie Rahma', 35.192, -0.65, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(14, 'Pharmacie El Fajr 2', 35.185, -0.655, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(15, 'Pharmacie El Wifak', 35.19, -0.66, 'Sidi Bel Abbes', 'Sidi Bel Abbes'),
(16, 'Pharmacie Amarnas 2', 35.153, -0.702, 'Sidi Bel Abbes', 'Amarnas'),
(17, 'Pharmacie Sidi Lahcene 2', 35.203, -0.684, 'Sidi Bel Abbes', 'Sidi Lahcene');

-- --------------------------------------------------------

--
-- Structure de la table `pharmacy_profiles`
--

CREATE TABLE `pharmacy_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pharmacy_name` varchar(255) DEFAULT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `is_profile_complete` tinyint(1) DEFAULT 0,
  `license_file` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `pharmacy_profiles`
--

INSERT INTO `pharmacy_profiles` (`id`, `user_id`, `pharmacy_name`, `license_number`, `wilaya`, `commune`, `is_profile_complete`, `license_file`, `lat`, `lng`) VALUES
(2, 90, 'el nour', '0909', 'sidi bel abbes', 'bellouladi', 1, NULL, NULL, NULL),
(4, 104, 'el nour', '9999999', 'sidi bel abbes', 'bellouladi', 1, 'uploads/licenses/pharmacy_6a246fc3d7b7c.png', 0.00000000, 0.00000000),
(5, 115, 'el nour', '9999999', 'sidi bel abbes', NULL, 1, 'uploads/licenses/pharmacy_6a24b1dee55ec.png', 0.00000000, 0.00000000),
(6, 193, 'el nour', '9999999', 'sidi bel abbes', NULL, 1, 'uploads/licenses/pharmacy_6a4a9af77f0f7.png', 0.00000000, 0.00000000);

-- --------------------------------------------------------

--
-- Structure de la table `pharmacy_requests`
--

CREATE TABLE `pharmacy_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `birth_info` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `service` varchar(255) DEFAULT NULL,
  `aile` varchar(20) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `diagnostic` text DEFAULT NULL,
  `rx_date` date DEFAULT NULL,
  `rx_time` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `medicines` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pharmacy_requests`
--

INSERT INTO `pharmacy_requests` (`id`, `patient_id`, `patient_name`, `birth_info`, `gender`, `room`, `service`, `aile`, `doctor_id`, `doctor_name`, `diagnostic`, `rx_date`, `rx_time`, `notes`, `medicines`, `status`, `created_at`) VALUES
(1, 44, 'najet', '', '', '', 'Service de Médecine Interne', '', 50, '', 'mardi', '2026-06-24', '23:04', '3 fois par jour avavnt', '[{\"name\":\"paracetamol\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"},{\"name\":\"ipoprofin\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"},{\"name\":\"hiptajil\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-24 21:04:30'),
(2, 46, 'saffae', '', 'أنثى', '', 'Service de Médecine Interne', 'women', 50, '', 'NCURLVF', '2026-06-25', '13:31', '3 FOIS', '[{\"name\":\"PARACETAMOL\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-25 11:31:35'),
(3, 47, 'mahmoud', '', '', '', 'Service de Médecine Interne', '', 50, '', 'vkjv\'pfw', '2026-06-25', '13:48', 'cdkjq\'IF', '[{\"name\":\"prctmol\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-25 11:48:18'),
(4, 53, 'ahmed', '', '', '', 'Service de Médecine Interne', '', 50, '', 'nchlh ykhdem', '2026-06-25', '20:51', '3la rabi', '[{\"name\":\"fkjborw\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-25 18:51:58'),
(5, 54, 'ahmed', '', '', '', 'Service de Médecine Interne', '', 50, '', '', '2026-06-25', '20:55', '', '[{\"name\":\"nchlh ykhdem\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-25 18:55:16'),
(6, 55, 'karim', '', 'ذكر', '', 'Service de Médecine Interne', 'men', 50, '', 'ya rabi ykhdem lyoum', '2026-06-26', '13:54', '', '[{\"name\":\"MCKDJO;IT\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-26 11:54:29'),
(7, 57, 'najato', '', 'أنثى', '', 'Service de Médecine Interne', 'women', 50, '', 'claude ai', '2026-06-26', '14:43', '', '[{\"name\":\"najato 2004\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-26 12:43:27'),
(8, 61, 'chikh', '', 'ذكر', '', 'Service de Médecine Interne', 'men', 50, '', 'm3lbliiiiiiiiiich', '2026-06-26', '21:52', '', '[{\"name\":\"m3lbliiiiiiiiiiiiiiiiiiiich\",\"dose\":\"\",\"freq\":\"\",\"duration\":\"\",\"route\":\"\"}]', 'pending', '2026-06-26 19:52:16');

-- --------------------------------------------------------

--
-- Structure de la table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `platform_settings`
--

INSERT INTO `platform_settings` (`key`, `value`, `updated_at`) VALUES
('email', 'nadjetkheira631@gmail.com', '2026-07-05 18:31:37'),
('logo_path', 'uploads/logos/platform_logo_1783276228.png', '2026-07-05 18:30:28'),
('phone', '06654961731', '2026-07-05 18:30:32'),
('platform_name', 'MedChifaGiz', '2026-07-05 18:31:37'),
('policy', 'يُلزم المستخدمون باحترام الشروط والأحكام المعمول بها في منصة MedChifaGiz. يُحظر نشر أي معلومات طبية مضللة أو الاستخدام غير المشروع للمنصة. تحتفظ الإدارة بحق تعطيل أي حساب يُخالف هذه السياسة.hhhhh', '2026-06-11 10:13:28'),
('website', 'www.medchifagiz.dz', '2026-07-05 18:31:37');

-- --------------------------------------------------------

--
-- Structure de la table `pregnancy_cards`
--

CREATE TABLE `pregnancy_cards` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `last_period_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `pregnancies_count` int(11) DEFAULT 0,
  `births_count` int(11) DEFAULT 0,
  `miscarriages_count` int(11) DEFAULT 0,
  `c_sections_count` int(11) DEFAULT 0,
  `chronic_diseases` text DEFAULT NULL,
  `father_status` varchar(255) DEFAULT NULL,
  `consanguinity` enum('yes','no') DEFAULT 'no',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pregnancy_followups`
--

CREATE TABLE `pregnancy_followups` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `sugar_level` varchar(50) DEFAULT NULL,
  `fetal_heartbeat` varchar(50) DEFAULT NULL,
  `fetal_movement` varchar(255) DEFAULT NULL,
  `fetal_weight` varchar(50) DEFAULT NULL,
  `fetal_position` varchar(100) DEFAULT NULL,
  `echo_notes` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `doctor_name` varchar(255) NOT NULL,
  `doctor_address` varchar(255) DEFAULT NULL,
  `rx_date` date NOT NULL,
  `medicines` text NOT NULL,
  `notes` text DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `patient_id`, `doctor_id`, `patient_name`, `doctor_name`, `doctor_address`, `rx_date`, `medicines`, `notes`, `signature`, `created_at`) VALUES
(1, 0, 49, '', 'kheira', 'sidi bel abbess', '2026-05-09', '', '', 'kheira', '2026-05-09 15:52:12'),
(2, 0, 49, '', 'kheira', 'sidi bel abbess', '2026-05-09', '', '', 'kheira', '2026-05-09 15:52:36'),
(3, 0, 49, 'nadjet', 'kheira', 'sidi bel abbess', '2026-05-09', 'paracetamol\r\ndoleprane', 'apres \r\n2 fois', 'Dr. kheira', '2026-05-09 16:00:48'),
(4, 0, 49, 'nadjet', 'kheira', 'sidi bel abbess', '2026-05-09', 'paracetamol', '2 fois\r\napres', 'Dr. kheira', '2026-05-09 16:04:15'),
(5, 0, 49, 'nadjet', 'kheira', 'sidi bel abbess', '2026-05-09', 'duliprane', '7fois', 'Dr. kheira', '2026-05-09 16:07:40'),
(6, 0, 49, 'nadjet', 'kheira', 'sidi bel abbess', '2026-05-09', 'duliprane', '7fois', 'Dr. kheira', '2026-05-09 16:09:44'),
(7, 0, 49, 'nadjet', 'kheira', 'sidi bel abbess', '2026-05-09', 'duliprane\r\nCNb;igr\r\nfangi\r\nFAJW', '7fois\r\n9FOIS PARE JOUER', 'Dr. kheira', '2026-05-09 16:10:07'),
(8, 0, 50, '', 'bennourcheikh', 'sba', '0000-00-00', '', '', '', '2026-05-25 20:06:22');

-- --------------------------------------------------------

--
-- Structure de la table `radiology_requests`
--

CREATE TABLE `radiology_requests` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `radiology_text` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `radiology_requests`
--

INSERT INTO `radiology_requests` (`id`, `patient_id`, `doctor_id`, `radiology_text`, `status`, `created_at`) VALUES
(1, 43, 50, 'echo abdomino', 'pending', '2026-06-24 19:52:50'),
(2, 46, 50, 'RX ECHO ABDOMINAUX', 'pending', '2026-06-25 11:31:35'),
(3, 47, 50, 'rx', 'pending', '2026-06-25 11:48:18'),
(4, 185, 50, 'rx', 'pending', '2026-06-25 18:50:58'),
(5, 185, 50, 'nchlh', 'pending', '2026-06-25 18:53:24'),
(6, 55, 50, 'nchlh ykhdem lyoum', 'pending', '2026-06-26 11:54:18'),
(7, 57, 50, 'claude hhhh', 'pending', '2026-06-26 12:43:27'),
(8, 61, 50, 'waaaaaaaaaaaaa', 'pending', '2026-06-26 19:51:43');

-- --------------------------------------------------------

--
-- Structure de la table `rapport_medical`
--

CREATE TABLE `rapport_medical` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rapport_date` date DEFAULT NULL,
  `rapport_patient` varchar(255) DEFAULT NULL,
  `rapport_age` varchar(50) DEFAULT NULL,
  `rapport_doctor` varchar(255) DEFAULT NULL,
  `rapport_content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rapport_medical`
--

INSERT INTO `rapport_medical` (`id`, `patient_id`, `rapport_date`, `rapport_patient`, `rapport_age`, `rapport_doctor`, `rapport_content`, `created_at`, `updated_at`) VALUES
(11, 42, '2026-05-30', 'Bachir', '60', 'bennourcheikh', 'حضر المريض يشكو من ألم أسفل البطن مع غازات وانتفاخ منذ يومين. بعد الفحص السريري تبين استقرار العلامات الحيوية مع عدم وجود علامات استعجالية. التشخيص المبدئي يشير إلى اضطراب هضمي أو التهاب معدة بسيط. تم وصف العلاج المناسب مع تقديم نصائح غذائية ومتابعة الحالة خلال الأيام القادمة.', '2026-05-30 18:41:06', '2026-05-30 18:41:06');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `service_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_rooms` tinyint(1) DEFAULT 0,
  `room_data` longtext DEFAULT NULL,
  `total_rooms` int(11) DEFAULT 0,
  `total_beds` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `clinic_id`, `name`, `service_admin_id`, `created_at`, `has_rooms`, `room_data`, `total_rooms`, `total_beds`, `is_active`) VALUES
(41, 96, 'medecin intene', NULL, '2026-06-20 16:19:44', 1, '{\"distType\":\"wings\",\"menRooms\":2,\"menBedsPerRoom\":2,\"womenRooms\":2,\"womenBedsPerRoom\":2}', 4, 8, 0),
(43, 180, 'neurologie', NULL, '2026-06-20 18:42:00', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(45, 180, 'medecin intene', NULL, '2026-06-21 11:19:54', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(46, 180, 'cardio', NULL, '2026-06-21 11:20:00', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(47, 180, 'tromato', NULL, '2026-06-21 11:22:29', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(48, 180, 'gastro', NULL, '2026-06-21 11:22:45', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(50, 180, 'hke,,m', NULL, '2026-06-21 20:42:44', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1),
(51, 180, 'mjjyy', NULL, '2026-06-21 20:52:15', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 0),
(56, 180, 'medecin intenellll', NULL, '2026-06-21 21:37:07', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 0),
(57, 180, 'loj', NULL, '2026-07-05 14:58:43', 1, '{\"distType\":\"wings\",\"menRooms\":0,\"menBedsPerRoom\":0,\"womenRooms\":0,\"womenBedsPerRoom\":0}', 0, 0, 1),
(58, 180, 'hakemdjefal', NULL, '2026-07-05 18:48:59', 1, '{\"distType\":\"wings\",\"menRooms\":2,\"menBedsPerRoom\":2,\"womenRooms\":3,\"womenBedsPerRoom\":2}', 5, 10, 1),
(60, 180, 'gty', NULL, '2026-07-05 18:55:46', 0, '{\"distType\":\"shared\",\"sharedRooms\":0,\"sharedBedsPerRoom\":0}', 0, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `specialties`
--

CREATE TABLE `specialties` (
  `id` int(11) NOT NULL,
  `name_fr` varchar(100) NOT NULL,
  `name_ar` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `specialties`
--

INSERT INTO `specialties` (`id`, `name_fr`, `name_ar`) VALUES
(1, 'general', 'طب عام'),
(2, 'cardiology', 'أمراض القلب'),
(3, 'gastroenterology', 'أمراض الجهاز الهضمي'),
(4, 'pediatrics', 'طب الأطفال'),
(5, 'gynecology', 'أمراض النساء والتوليد'),
(6, 'dermatology', 'أمراض الجلد'),
(7, 'ophthalmology', 'طب العيون'),
(8, 'ent', 'أمراض الأنف والأذن والحنجرة'),
(9, 'neurology', 'أمراض الجهاز العصبي'),
(10, 'pulmonology', 'أمراض الرئة'),
(11, 'surgery', 'الجراحة العامة'),
(12, 'orthopedics', 'جراحة العظام'),
(13, 'dentist', 'طب الأسنان'),
(14, 'psychiatry', 'الطب النفسي'),
(15, 'endocrinology', 'الغدد الصماء والسكري'),
(16, 'hematology', 'أمراض الدم'),
(17, 'emergency', 'طب الطوارئ'),
(18, 'rheumatology', 'أمراض الروماتيزم'),
(19, 'oncology', 'طب الأورام'),
(20, 'cosmetic', 'جراحة تجميلية'),
(21, 'sports', 'طب الرياضة'),
(22, 'anesthesia', 'التخدير'),
(23, 'radiology', 'الأشعة الطبية');

-- --------------------------------------------------------

--
-- Structure de la table `sport_health`
--

CREATE TABLE `sport_health` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `wilaya` varchar(100) DEFAULT NULL,
  `commune` varchar(100) DEFAULT NULL,
  `sub_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sport_health`
--

INSERT INTO `sport_health` (`id`, `name`, `lat`, `lng`, `wilaya`, `commune`, `sub_type`) VALUES
(1, 'Centre Santé & Sport 1', 35.189, -0.63, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'nutrition'),
(2, 'Fitness Santé Plus', 35.192, -0.6362, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'sport'),
(3, 'Salle Sport Amarnas', 35.1538, -0.7015, 'Sidi Bel Abbes', 'Amarnas', 'sport'),
(4, 'Club Santé Sidi Lahcene', 35.204, -0.685, 'Sidi Bel Abbes', 'Sidi Lahcene', 'nutrition'),
(5, 'Vital Sport SBA', 35.1882, -0.6285, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'sport'),
(6, 'Nutrition Center SBA', 35.19, -0.63, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'nutrition'),
(7, 'Healthy Life Clinic', 35.195, -0.64, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'nutrition'),
(8, 'Diet Pro Center', 35.2, -0.65, 'Sidi Bel Abbes', 'Amarnas', 'nutrition'),
(9, 'Gym Power House', 35.205, -0.66, 'Sidi Bel Abbes', 'Sidi Bel Abbes', 'sport'),
(10, 'Fitness Club Pro', 35.21, -0.67, 'Sidi Bel Abbes', 'Amarnas', 'sport'),
(11, 'Iron Gym SBA', 35.215, -0.68, 'Sidi Bel Abbes', 'Sidi Lahcene', 'sport');

-- --------------------------------------------------------

--
-- Structure de la table `super_admin_notifications`
--

CREATE TABLE `super_admin_notifications` (
  `id` int(11) NOT NULL,
  `super_admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `super_admin_notifications`
--

INSERT INTO `super_admin_notifications` (`id`, `super_admin_id`, `title`, `message`, `type`, `is_read`, `related_id`, `related_type`, `created_at`) VALUES
(2, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـطبيب. [ID:175]', 'info', 1, NULL, NULL, '2026-06-11 16:40:54'),
(3, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـمخبر تحاليل. [ID:176]', 'info', 1, NULL, NULL, '2026-06-11 19:09:36'),
(4, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـعيادة. [ID:177]', 'info', 1, NULL, NULL, '2026-06-11 19:21:15'),
(5, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـعيادة. [ID:178]', 'info', 1, NULL, NULL, '2026-06-11 19:23:52'),
(6, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـعيادة. [ID:179]', 'info', 1, NULL, NULL, '2026-06-11 21:20:41'),
(7, 96, 'طلب تسجيل جديد', 'تقدّم Nadjet Hakem بطلب تسجيل كـعيادة. [ID:180]', 'info', 1, NULL, NULL, '2026-06-16 16:03:21'),
(8, 96, 'طلب تسجيل جديد', 'تقدّم bennourcheikh بطلب تسجيل كـطبيب. [ID:80]', 'info', 1, NULL, NULL, '2026-06-18 11:15:06'),
(9, 96, 'طلب تسجيل جديد', 'تقدّم hina بطلب تسجيل كـعيادة. [ID:181]', 'info', 1, NULL, NULL, '2026-06-21 10:19:45'),
(10, 96, 'طلب تسجيل جديد', 'تقدّم jiji بطلب تسجيل كـطبيب. [ID:189]', 'info', 1, NULL, NULL, '2026-07-05 11:07:19'),
(11, 96, 'طلب تسجيل جديد', 'تقدّم nadj بطلب تسجيل كـمخبر تحاليل. [ID:191]', 'info', 1, NULL, NULL, '2026-07-05 17:50:17'),
(12, 96, 'طلب تسجيل جديد', 'تقدّم nadjety بطلب تسجيل كـصيدلية. [ID:193]', 'info', 1, NULL, NULL, '2026-07-05 17:57:31'),
(13, 96, 'طلب تسجيل جديد', 'تقدّم hina بطلب تسجيل كـعيادة. [ID:196]', 'info', 1, NULL, NULL, '2026-07-05 19:12:50'),
(14, 96, 'طلب تسجيل جديد', 'تقدّم hina بطلب تسجيل كـعيادة. [ID:197]', 'info', 1, NULL, NULL, '2026-07-05 19:19:10'),
(15, 96, 'طلب تسجيل جديد', 'تقدّم kheira بطلب تسجيل كـطبيب. [ID:198]', 'info', 1, NULL, NULL, '2026-07-05 19:25:58'),
(16, 96, 'طلب تسجيل جديد', 'تقدّم nadjet بطلب تسجيل كـطبيب. [ID:199]', 'info', 1, NULL, NULL, '2026-07-06 11:45:44');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('patient','doctor','pharmacy','clinic','lab','super_admin','admin','moderator') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_otp` varchar(10) DEFAULT NULL,
  `otp_expire` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `twofa_secret` varchar(255) DEFAULT NULL,
  `twofa_enabled` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `account_status` varchar(20) NOT NULL DEFAULT 'active',
  `permissions` longtext DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL COMMENT 'مسار صورة البروفايل المحفوظة في uploads/avatars/',
  `last_login` datetime DEFAULT NULL,
  `previous_login` datetime DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `created_at`, `reset_otp`, `otp_expire`, `otp_attempts`, `twofa_secret`, `twofa_enabled`, `status`, `rejection_reason`, `profile_completed`, `account_status`, `permissions`, `profile_picture`, `last_login`, `previous_login`, `last_seen`, `is_online`) VALUES
(79, 'Nadjet Hakem', 'ghermaouiamina31@gmail.com', NULL, '$2y$10$u2FMhYm9hPebxDiqLyQaaOe7bYoXWDUzPXZvsEXdQo1fA2O/DEaGS', 'patient', '2026-04-30 11:23:16', NULL, NULL, 0, '7OZ6VHHQWT4IPKXD', 0, 'approved', NULL, 0, 'active', NULL, NULL, NULL, NULL, NULL, 0),
(80, 'bennourcheikh', 'bennourcheikh.amsp@gmail.com', '0987', '$2y$10$o0IWDa6s3aUBT5u.gGoWrO.u0BtVoNZxgXtiVOkg11QhqNwW/PAHG', 'doctor', '2026-05-05 14:03:14', NULL, NULL, 0, 'CYMZNAFVELZ2YAKK', 0, 'approved', NULL, 1, 'active', NULL, 'uploads/avatars/avatar_80_1781179426.png', NULL, NULL, '2026-07-06 14:05:40', 1),
(81, 'djefal kheira', 'djefalkheira@gmail.com', NULL, '$2y$10$SJU5WizDU7n6hmh3GAXwZuKV7lIpxXMKcJk/dnzjt4Mk5jGhcluh.', 'doctor', '2026-05-06 12:01:06', NULL, NULL, 0, 'MOLM7GNPZZLBFIIJ', 0, 'approved', NULL, 1, 'active', NULL, NULL, NULL, NULL, NULL, 0),
(96, 'nadjet kheira', 'nadjetkheira631@gmail.com', NULL, '$2y$10$X31UmY56MNo2SoOulnbGRO4F12EtuhG3N.ThXBx6pBTjK2aTqrG9W', 'super_admin', '2026-06-05 18:49:25', NULL, NULL, 0, 'IFJSMOHKGGJOP3MZ', 0, 'approved', NULL, 0, 'active', NULL, 'uploads/avatars/avatar_96_1781188739.png', NULL, NULL, '2026-07-05 20:01:31', 0),
(153, 'modirator', 'modiratoor', NULL, '$2y$10$OtdSye4RxUu/OJcs1X77lekOgczBnB25vGZ26Q9Rctqu2khhIRw8O', 'moderator', '2026-06-09 15:15:45', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":false,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":true,\"manageRequests\":true,\"viewInstitutions\":false,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":true,\"manageMaintenance\":false,\"viewActivities\":false,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(154, 'modirator', 'modiratoor@gmail.com', NULL, '$2y$10$gTkp2OdBnOyjZFabaSgB8e/4TsCKjs/HI9uo3jMHLCeBdBPMtxwu6', 'moderator', '2026-06-09 15:16:33', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":false,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":true,\"manageRequests\":true,\"viewInstitutions\":false,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":true,\"manageMaintenance\":false,\"viewActivities\":false,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(155, 'modiratorrr', 'modiratorrr@gmail.com', NULL, '$2y$10$dpHeazqIKxkCUP7WBrof5uUILCbvY7gSKTt3QvGSVyzEKn1Puzi3u', 'moderator', '2026-06-09 15:26:22', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":true,\"manageRequests\":false,\"viewInstitutions\":false,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":false,\"manageMaintenance\":false,\"manageAdmins\":false,\"viewActivities\":false}', NULL, NULL, NULL, NULL, 0),
(157, 'admin', '', NULL, '$2y$10$rBkCMbepNHpfmtPkbsTtq.BZAPenNvJ722ptmvjuO79AO5WmDMx7G', '', '2026-06-09 15:35:05', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":true,\"manageRequests\":false,\"viewInstitutions\":true,\"manageInstitutions\":false,\"viewStats\":true,\"manageSettings\":false,\"manageMaintenance\":false,\"viewActivities\":false,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(160, 'kiko', 'kiko@gmail.com', NULL, '$2y$10$UmLBvBhMuhAk7jGqNxifp.uJ1iZPpqBF4KJmJCORJWsOG8c9NdTK6', 'moderator', '2026-06-09 15:56:53', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"editUsers\":true,\"deleteUsers\":true,\"viewRequests\":true,\"manageRequests\":true,\"viewInstitutions\":true,\"manageInstitutions\":true,\"viewStats\":false,\"manageSettings\":false,\"manageMaintenance\":false,\"viewActivities\":false,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(161, 'admino', 'admino@gmail.com', NULL, '$2y$10$72xdc0vtQ7DsjT9frJCMjug91feuxZR6AgM5PA1jU/XNv2pLWUN42', 'admin', '2026-06-09 16:03:46', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":true,\"manageRequests\":false,\"viewInstitutions\":true,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":false,\"manageMaintenance\":false,\"viewActivities\":false,\"manageAdmins\":true}', NULL, NULL, NULL, NULL, 0),
(163, 'hhhhdh', 'hhh@gmail.com', NULL, '$2y$10$g7ntQ0IYCQKHUHi32msToOycXScF/pT3o/JlvRq6MFC5QDH/GlUtu', 'moderator', '2026-06-09 17:36:10', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":false,\"editUsers\":false,\"deleteUsers\":false,\"viewRequests\":false,\"manageRequests\":false,\"viewInstitutions\":false,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":false,\"manageMaintenance\":false,\"viewActivities\":false,\"viewAdmins\":true,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(164, 'gh', 'gh@gmail.com', NULL, '$2y$10$YVLmLrb3XcLCz5Te3I1aMOAzytN3R9hZyUDnfGIBsG/HZIwdNl8EW', 'moderator', '2026-06-09 18:26:53', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"editUsers\":true,\"deleteUsers\":true,\"viewRequests\":false,\"manageRequests\":false,\"viewInstitutions\":false,\"manageInstitutions\":false,\"viewStats\":false,\"manageSettings\":false,\"manageMaintenance\":false,\"viewActivities\":false,\"viewAdmins\":false,\"manageAdmins\":false}', NULL, NULL, NULL, NULL, 0),
(180, 'Nadjet Hakem', 'nadjethakem11@gmail.com', '0987654', '$2y$10$Zy235BxcjppXConifXuyO.k2pujCNONO86kcD2yCqo3XfX3aruAHm', 'clinic', '2026-06-16 16:02:44', NULL, NULL, 0, 'HLBJFTQ3TGALIBYW', 0, 'approved', NULL, 1, 'active', NULL, NULL, '2026-07-06 00:57:52', '2026-07-06 00:53:45', '2026-07-06 02:02:26', 1),
(188, 'inomina', 'hakemnadjet11@gmail.com', NULL, '$2y$10$vw3OTkjkrtGov0Gm1ImfE.CpJRX9hLpqGxKPrNseAke3g2HaXr5HS', 'patient', '2026-07-01 20:51:14', NULL, NULL, 0, 'FIABCJSIPDWELABV', 0, 'approved', NULL, 0, 'active', NULL, NULL, NULL, NULL, '2026-07-06 14:01:37', 1),
(194, 'khyroraty', 'khyro@gmail.com', NULL, '$2y$10$gx74.hfL5ew/K8oqtTHCreoHrZGzJK0C/cEhguwfvBPIRpM0oRWZy', 'moderator', '2026-07-05 18:07:46', NULL, NULL, 0, NULL, 0, 'approved', NULL, 0, 'active', '{\"viewUsers\":true,\"manageUsers\":true,\"viewRequests\":true,\"manageRequests\":true,\"viewStats\":true,\"manageSettings\":true,\"manageMaintenance\":true,\"viewActivities\":false,\"viewAdmins\":false,\"manageAdmins\":true}', NULL, NULL, NULL, '2026-07-05 20:09:42', 0),
(198, 'kheira', 'jfalkhyrt@gmail.com', '999999', '$2y$10$60a7Q6i9ef8Sr9eRniLNUO1yzeBWBdnlXL//pdSEnxqVI/34vUdOy', 'doctor', '2026-07-05 19:25:18', NULL, NULL, 0, 'AR6AVMNSINC2B56J', 0, 'approved', NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-07-06 00:55:52', 1),
(199, 'nadjet', 'hinanadjet@gmail.com', '0987', '$2y$10$A8VN7dj5TrKiDRmiJjurP.oFrADygh3QN/CEJIkcW6yZ/PCtFUmUC', 'doctor', '2026-07-06 11:44:46', NULL, NULL, 0, 'VMWM45QPOQMG3C7V', 0, 'approved', NULL, 1, 'active', NULL, NULL, NULL, NULL, '2026-07-06 13:54:37', 1);

-- --------------------------------------------------------

--
-- Structure de la table `wilayas`
--

CREATE TABLE `wilayas` (
  `id` int(11) NOT NULL,
  `name_fr` varchar(100) NOT NULL,
  `name_ar` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `wilayas`
--

INSERT INTO `wilayas` (`id`, `name_fr`, `name_ar`) VALUES
(1, 'Adrar', 'أدرار'),
(2, 'Chlef', 'الشلف'),
(3, 'Laghouat', 'الأغواط'),
(4, 'Oum El Bouaghi', 'أم البواقي'),
(5, 'Batna', 'باتنة'),
(6, 'Bejaia', 'بجاية'),
(7, 'Biskra', 'بسكرة'),
(8, 'Bechar', 'بشار'),
(9, 'Blida', 'البليدة'),
(10, 'Bouira', 'البويرة'),
(11, 'Tamanrasset', 'تمنراست'),
(12, 'Tebessa', 'تبسة'),
(13, 'Tlemcen', 'تلمسان'),
(14, 'Tiaret', 'تيارت'),
(15, 'Tizi Ouzou', 'تيزي وزو'),
(16, 'Alger', 'الجزائر'),
(17, 'Djelfa', 'الجلفة'),
(18, 'Jijel', 'جيجل'),
(19, 'Setif', 'سطيف'),
(20, 'Saida', 'سعيدة'),
(21, 'Skikda', 'سكيكدة'),
(22, 'Sidi Bel Abbes', 'سيدي بلعباس'),
(23, 'Annaba', 'عنابة'),
(24, 'Guelma', 'قالمة'),
(25, 'Constantine', 'قسنطينة'),
(26, 'Medea', 'المدية'),
(27, 'Mostaganem', 'مستغانم'),
(28, 'Msila', 'المسيلة'),
(29, 'Mascara', 'معسكر'),
(30, 'Ouargla', 'ورقلة'),
(31, 'Oran', 'وهران'),
(32, 'El Bayadh', 'البيض'),
(33, 'Illizi', 'إليزي'),
(34, 'Bordj Bou Arreridj', 'برج بوعريريج'),
(35, 'Boumerdes', 'بومرداس'),
(36, 'El Tarf', 'الطارف'),
(37, 'Tindouf', 'تندوف'),
(38, 'Tissemsilt', 'تيسمسيلت'),
(39, 'El Oued', 'الوادي'),
(40, 'Khenchela', 'خنشلة'),
(41, 'Souk Ahras', 'سوق أهراس'),
(42, 'Tipaza', 'تيبازة'),
(43, 'Mila', 'ميلة'),
(44, 'Ain Defla', 'عين الدفلى'),
(45, 'Naama', 'النعامة'),
(46, 'Ain Temouchent', 'عين تموشنت'),
(47, 'Ghardaia', 'غرداية'),
(48, 'Relizane', 'غليزان'),
(49, 'Timimoun', 'تيميمون'),
(50, 'Bordj Badji Mokhtar', 'برج باجي مختار'),
(51, 'Ouled Djellal', 'أولاد جلال'),
(52, 'Beni Abbes', 'بني عباس'),
(53, 'In Salah', 'عين صالح'),
(54, 'In Guezzam', 'عين قزام'),
(55, 'Touggourt', 'تقرت'),
(56, 'Djanet', 'جانت'),
(57, 'El Meghaier', 'المغير'),
(58, 'El Meniaa', 'المنيعة'),
(59, 'Aflou', 'أفلو'),
(60, 'Barika', 'بريكة'),
(61, 'Ksar Chellala', 'قصر الشلالة'),
(62, 'Messaad', 'مسعد'),
(63, 'Ain Oussera', 'عين وسارة'),
(64, 'Boussaada', 'بوسعادة'),
(65, 'El Abiodh Sidi Cheikh', 'الأبيض سيدي الشيخ'),
(66, 'El Kantara', 'القنطرة'),
(67, 'Bir El Ater', 'بئر العاتر'),
(68, 'Ksar El Boukhari', 'قصر البخاري'),
(69, 'El Aricha', 'العريشة');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ai_file_organization`
--
ALTER TABLE `ai_file_organization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_record` (`medical_record_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_priority` (`priority`);

--
-- Index pour la table `ai_medical_reports`
--
ALTER TABLE `ai_medical_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_record` (`medical_record_id`);

--
-- Index pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `archived_records`
--
ALTER TABLE `archived_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archived_medical_record` (`medical_record_id`);

--
-- Index pour la table `civil_protection`
--
ALTER TABLE `civil_protection`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clinic_profiles`
--
ALTER TABLE `clinic_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `clinic_staff`
--
ALTER TABLE `clinic_staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Index pour la table `communes`
--
ALTER TABLE `communes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wilaya_id` (`wilaya_id`);

--
-- Index pour la table `consultation_cases`
--
ALTER TABLE `consultation_cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `idx_clinic` (`clinic_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_assigned_doctor` (`assigned_doctor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_scope` (`consultation_scope`);

--
-- Index pour la table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_case_created` (`consultation_case_id`,`created_at`);

--
-- Index pour la table `consultation_participants`
--
ALTER TABLE `consultation_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_case_doctor` (`consultation_case_id`,`doctor_id`,`doctor_type`),
  ADD KEY `idx_case` (`consultation_case_id`);

--
-- Index pour la table `daily_journal`
--
ALTER TABLE `daily_journal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`entry_date`),
  ADD KEY `idx_user_date` (`user_id`,`entry_date`);

--
-- Index pour la table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Index pour la table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `fiche_traitement`
--
ALTER TABLE `fiche_traitement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_record` (`medical_record_id`);

--
-- Index pour la table `labs`
--
ALTER TABLE `labs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `lab_profiles`
--
ALTER TABLE `lab_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `maintenance_settings`
--
ALTER TABLE `maintenance_settings`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `medical_followups`
--
ALTER TABLE `medical_followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_record_id` (`medical_record_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`);

--
-- Index pour la table `medical_history_log`
--
ALTER TABLE `medical_history_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_record` (`record_id`),
  ADD KEY `idx_field` (`record_id`,`field_key`);

--
-- Index pour la table `medical_messages`
--
ALTER TABLE `medical_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_record` (`record_id`),
  ADD KEY `idx_patient` (`patient_user_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_thread` (`record_id`,`created_at`);

--
-- Index pour la table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_residency` (`doctor_id`,`residency_status`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `nurses`
--
ALTER TABLE `nurses`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `nurse_treatments`
--
ALTER TABLE `nurse_treatments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_nurse_patient` (`patient_id`),
  ADD KEY `idx_nurse_status` (`status`),
  ADD KEY `idx_nurse_aile` (`aile`);

--
-- Index pour la table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`);

--
-- Index pour la table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pharmacy_profiles`
--
ALTER TABLE `pharmacy_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pharmacy_requests`
--
ALTER TABLE `pharmacy_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pharmacy_status` (`status`),
  ADD KEY `idx_pharmacy_patient` (`patient_id`);

--
-- Index pour la table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `pregnancy_cards`
--
ALTER TABLE `pregnancy_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`);

--
-- Index pour la table `pregnancy_followups`
--
ALTER TABLE `pregnancy_followups`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `rapport_medical`
--
ALTER TABLE `rapport_medical`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patient` (`patient_id`),
  ADD UNIQUE KEY `patient_id` (`patient_id`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `service_admin_id` (`service_admin_id`);

--
-- Index pour la table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sport_health`
--
ALTER TABLE `sport_health`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `super_admin_notifications`
--
ALTER TABLE `super_admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_super_admin` (`super_admin_id`),
  ADD KEY `idx_read_status` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `wilayas`
--
ALTER TABLE `wilayas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT pour la table `ai_file_organization`
--
ALTER TABLE `ai_file_organization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `ai_medical_reports`
--
ALTER TABLE `ai_medical_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT pour la table `archived_records`
--
ALTER TABLE `archived_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT pour la table `civil_protection`
--
ALTER TABLE `civil_protection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `clinic_profiles`
--
ALTER TABLE `clinic_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `clinic_staff`
--
ALTER TABLE `clinic_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=186;

--
-- AUTO_INCREMENT pour la table `communes`
--
ALTER TABLE `communes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=872;

--
-- AUTO_INCREMENT pour la table `consultation_cases`
--
ALTER TABLE `consultation_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `consultation_participants`
--
ALTER TABLE `consultation_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `daily_journal`
--
ALTER TABLE `daily_journal`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT pour la table `donors`
--
ALTER TABLE `donors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `fiche_traitement`
--
ALTER TABLE `fiche_traitement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `labs`
--
ALTER TABLE `labs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `lab_profiles`
--
ALTER TABLE `lab_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `lab_requests`
--
ALTER TABLE `lab_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `maintenance_log`
--
ALTER TABLE `maintenance_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `medical_followups`
--
ALTER TABLE `medical_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `medical_history_log`
--
ALTER TABLE `medical_history_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `medical_messages`
--
ALTER TABLE `medical_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT pour la table `nurses`
--
ALTER TABLE `nurses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `nurse_treatments`
--
ALTER TABLE `nurse_treatments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `pharmacies`
--
ALTER TABLE `pharmacies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `pharmacy_profiles`
--
ALTER TABLE `pharmacy_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `pharmacy_requests`
--
ALTER TABLE `pharmacy_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `pregnancy_cards`
--
ALTER TABLE `pregnancy_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pregnancy_followups`
--
ALTER TABLE `pregnancy_followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `rapport_medical`
--
ALTER TABLE `rapport_medical`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `sport_health`
--
ALTER TABLE `sport_health`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `super_admin_notifications`
--
ALTER TABLE `super_admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `clinic_profiles`
--
ALTER TABLE `clinic_profiles`
  ADD CONSTRAINT `clinic_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `clinic_staff`
--
ALTER TABLE `clinic_staff`
  ADD CONSTRAINT `fk_clinic_staff_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `communes`
--
ALTER TABLE `communes`
  ADD CONSTRAINT `communes_ibfk_1` FOREIGN KEY (`wilaya_id`) REFERENCES `wilayas` (`id`);

--
-- Contraintes pour la table `consultation_messages`
--
ALTER TABLE `consultation_messages`
  ADD CONSTRAINT `fk_consultation_messages_case` FOREIGN KEY (`consultation_case_id`) REFERENCES `consultation_cases` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `consultation_participants`
--
ALTER TABLE `consultation_participants`
  ADD CONSTRAINT `fk_consultation_participants_case` FOREIGN KEY (`consultation_case_id`) REFERENCES `consultation_cases` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `fk_doctors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `lab_profiles`
--
ALTER TABLE `lab_profiles`
  ADD CONSTRAINT `lab_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
