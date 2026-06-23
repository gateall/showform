
# STAGE 01 - DB 구축

## 목표

ShowForm 랜딩페이지 시스템의 기본 DB 구조를 구축한다.

---

## 생성 테이블

### landing_pages

랜딩페이지 기본정보 저장

필수 컬럼

* id
* template_type
* industry
* company_name
* ceo_name
* phone
* kakao_url
* address
* area_name
* intro_text
* main_copy
* sub_copy
* theme_color
* main_image
* is_active
* created_at
* updated_at

---

### landing_inquiries

문의 저장

필수 컬럼

* id
* landing_id
* name
* phone
* message
* status
* ip
* created_at

---

### landing_reviews

후기 저장

필수 컬럼

* id
* landing_id
* customer_name
* rating
* content
* sort_order
* is_active
* created_at

---

### landing_gallery

갤러리 저장

필수 컬럼

* id
* landing_id
* title
* image_path
* description
* sort_order
* is_active
* created_at

---

### landing_youtube

유튜브 저장

필수 컬럼

* id
* landing_id
* title
* youtube_url
* description
* sort_order
* is_active
* created_at

---

### landing_notices

공지사항 저장

필수 컬럼

* id
* landing_id
* title
* content
* is_active
* created_at

---

## 추가 작업

생성 파일

/adm/landing/install.php

기능

* 테이블 존재 확인
* 없으면 생성
* 결과 출력

---

## 샘플 데이터

3건 생성

* 서비스형
* 병원형
* 지역업체형

---

## 완료 기준

* DB 생성 완료
* UTF8MB4 적용
* 샘플 데이터 등록
* PHP Error 없음
* install.php 재실행 가능
