
# Car Insurance Policy API Documentation

This API provides endpoints for managing entities related to car insurance, including accounts, car brands, car types, insurance policies, and rates. This documentation outlines each endpoint’s functionality, usage, and expected data formats.

## Table of Contents
- [Overview](#overview)
- [Base URL](#base-url)
- [Endpoints](#endpoints)
  - [Account Endpoints](#account-endpoints)
  - [Car Brand Endpoints](#car-brand-endpoints)
  - [Car Type Endpoints](#car-type-endpoints)
  - [Insurance Policy Endpoints](#insurance-policy-endpoints)
  - [Rate Endpoints](#rate-endpoints)
- [Error Handling](#error-handling)
- [Examples](#examples)

---

## Overview

The **Car Insurance Policy API** allows for the management of various entities necessary for handling car insurance policies. Each endpoint allows for creating, viewing, updating, and deleting records within each entity.

## Base URL
The base URL for all API requests is:
```plaintext
https://www.kevinngabriell.com/tobInsAPI-v.1.0
```

## Endpoints

### Account Endpoints (`account.php`)

#### 1. Create Account
- **URL**: `/account/account.php`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "username": "johndoe",
    "name": "John Doe"
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "account_uid": "unique_account_id"
  }
  ```

#### 2. Get Account Details
- **URL**: `account/account.php/{account_uid}`
- **Method**: `GET`
- **Description**: Retrieves details of a specific account.

### Car Brand Endpoints (`carbrand.php`)

#### 1. Create Car Brand
- **URL**: `car/carbrand.php`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "name": "Toyota"
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "car_brand_uid": "unique_car_brand_id"
  }
  ```

#### 2. List All Car Brands
- **URL**: `car/carbrand.php`
- **Method**: `GET`
- **Description**: Retrieves a list of all car brands.

### Car Type Endpoints (`cartype.php`)

#### 1. Create Car Type
- **URL**: `car/cartype.php`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "name": "SUV",
    "brand": "unique_car_brand_id"
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "car_type_uid": "unique_car_type_id"
  }
  ```

#### 2. List All Car Types
- **URL**: `car/cartype.php`
- **Method**: `GET`
- **Description**: Retrieves a list of all car types with brand associations.

### Insurance Policy Endpoints (`insurance.php`)

#### 1. Create Policy
- **URL**: `insurance/insurance.php`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "insured": "account_uid",
    "car_brand": "car_brand_uid",
    "car_type": "car_type_uid",
    "car_year": 2023,
    "car_price": 25000,
    "premium_rate": 4.0
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "policy_number": "unique_policy_number"
  }
  ```

#### 2. Get Policy Details
- **URL**: `insurance/insurance.php/{policy_number}`
- **Method**: `GET`
- **Description**: Retrieves details of a specific insurance policy.

### Rate Endpoints (`rate.php`)

#### 1. Create Rate
- **URL**: `rate/rate.php`
- **Method**: `POST`
- **Payload**:
  ```json
  {
    "rate": 4.0
  }
  ```
- **Response**:
  ```json
  {
    "status": "success",
    "rate_uid": "unique_rate_id"
  }
  ```

#### 2. List All Rates
- **URL**: `rate/rate.php`
- **Method**: `GET`
- **Description**: Retrieves a list of all rates.

---

## Error Handling

The API uses standard HTTP status codes to indicate success or failure. Below are common responses:

- **200 OK**: Successful request
- **400 Bad Request**: Invalid request parameters
- **401 Unauthorized**: Invalid or missing API key
- **404 Not Found**: Resource not found
- **500 Internal Server Error**: Server encountered an unexpected error

Example error response:
```json
{
  "status": "error",
  "message": "Invalid request parameters"
}
```
