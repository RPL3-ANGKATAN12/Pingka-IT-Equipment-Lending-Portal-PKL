@extends('layouts.app')

@section('title', 'Home')

@section('content')

<div class="home-container">

    <div class="home-content">

        <h1>IT Equipment Lending Portal</h1>

        <p>
            Welcome to the IT Equipment Lending Portal.
            Please select the menu you would like to open.
        </p>

        <div class="home-buttons">

            <a href="{{ route('guest.items') }}" class="home-button">
                <div class="button-icon">📦</div>

                <div class="button-text">
                    <strong>Available Items</strong>
                    <span>
                        View IT equipment currently available for loan.
                    </span>
                </div>
            </a>

            <a href="{{ route('guest.loans') }}" class="home-button">
                <div class="button-icon">📋</div>

                <div class="button-text">
                    <strong>Loan Data</strong>
                    <span>
                        View data of items currently on loan.
                    </span>
                </div>
            </a>

        </div>

    </div>

</div>


@endsection