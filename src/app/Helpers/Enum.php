<?php

namespace App\Helpers;

enum TypeNotify: string
{
    case Text = 'text';
    case Photo = 'photo';
}

enum TypeTopic: string
{
    case START = 'start';
    case SUCCESS = 'success';
    case ERROR = 'error';
}

enum TypeDB: string
{
    case PGSQL = 'pgsql';
    case MYSQL = 'mysql';
}

enum TypeTask: string
{
        //--- wb
    case Incomes = 'incomes';
    case Stocks = 'stocks';
    case Orders = 'orders';
    case Sales = 'sales';
    case SalesReports = 'reportDetailByPeriod';
    case ExciseReports = 'excise-goods';
        //--- ozon
    case StockWarehouses = 'stock-warehouses';
    case FboList = 'fbo-list';
    case ReportStocks = 'report-stocks';
        //--- ozon-performance
    case Campaign = 'campaign';
    case StatDaily = 'statistics-daily';
    case StatMediaCampaign = 'statistics-media-compaign';
    case StatFoodCampaign = 'statistics-food-compaign';
    case StatExpenseCampaign = 'statistics-expense-compaign';
        //--- roistat
    case Data = 'data';
    case ListIntegration = 'list-integration';
//    case VisitList = 'visit-list';
}   