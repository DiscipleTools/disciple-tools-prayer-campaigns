import {css} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';

export const campaignStyles = css`
  .cp-wrapper {
    max-width:1000px;
    margin: 0 auto;
    padding: 2em 1em;
    border-radius: 10px;
    font-size: 1em;
    min-width: 320px;
  }

  .cp-center {
    text-align: center;
  }
  strong, b {
    font-weight: bold;
  }
  .cp-wrapper {
    font-size: 1rem;
  }
  h3 {
    font-size: 1.5rem;
  }
  h2 {
    font-size: 1.7rem;
  }
  h1, h2, h3 {
    color: black;
  }

  p {
    margin: 10px 0;
  }


  .cp-wrapper.loading-content h2, .cp-wrapper.loading-content p {
    background-color: #ededed;
    border-radius: 100px;
    min-width: 100px;
    min-height: 20px;
    margin-bottom: 5px;
  }

  button {
    color: #fefefe;
    font-size: 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    font-weight: normal;
    padding: .85em 1em;
    cursor:pointer;
  }
  button:hover {
    background-color: transparent;
  }
  button[disabled] {
    opacity: .25;
    cursor: not-allowed;
  }
  button.clear-button {
    background-color: transparent;
    /*color: black;*/
    padding:5px;
  }


  /**
   * Calendar section
   */
  .cp-calendar-wrapper {
    background-color: #f8f9fad1;
    border-radius: 10px;
    padding: 1em
  }

  .day-cell, .cp-calendar-wrapper .day-cell {
    text-align: center;
    flex-grow: 0;
  }

  .cp-calendar-wrapper .disabled-calendar-day {
    width:40px;
    height:40px;
    vertical-align: top;
    padding-top:10px;
    color: grey;
  }

  .cp-calendar-wrapper .calendar {
    display: flex;
    flex-wrap: wrap;
    width: 300px;
    margin: auto
  }
  .cp-calendar-wrapper .month-title {
    text-align: left;
    margin-bottom: 10px;
  }
  .cp-calendar-wrapper .week-day {
    height: 20px;
    width:40px;
    color:black;
    font-size:12px;
    font-weight:550;
    margin-bottom:5px;
  }


  .day-in-select-calendar {
    color: black;
    display: inline-block;
    height: 40px;
    width: 40px;
    line-height: 0;
    vertical-align: middle;
    text-align: center;
    padding-top: 18px;
  }

  .selected-day {
    color: white;
    border-radius: 50%;
    border: 2px solid;
  }
  



  /**
   * Sign up section
   */
  #email {
    display:none;
  }

  select {
    font-size: 1rem;
    line-height: 1rem;
    color: black;
    border: 1px solid black ;
    display: block;
    min-width: 250px;
    max-width: 400px;
    background: white;
    margin: 10px auto;
    min-height: 40px;
    padding:0.5em;
  }

  input {
    font-size: 1rem;
    line-height: 1rem;
    color: black;
    border: 1px solid black;
  }
  .cp-input {
    min-width: 250px;
    max-width: 400px;
    margin: auto;
    padding:0.5em;
    min-height: 40px;
    display: block;
  }

  .cp-close-button {
    top: .5rem;
    font-size: 1em;
    line-height: 1;
    display: block;
    cursor:pointer;
    padding: 5px;
  }

  .cp-close-button img {
    filter: invert(100%);
    height: 15px;
    width: 15px;
    vertical-align: bottom;
  }

  .nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 1em;
  }
  .button-spinner {
    filter: invert(1);
  }

  label {
    font-size: 1rem;
    margin-bottom: .5rem;
  }
  .form-error {
    color: red;
  }

  .remove-prayer-time-button, .remove-daily-time-button {
    background-color: transparent;
    color: red;
    /* border: black 1px solid; */
    padding: 0 5px;
  }
  .cp-display-selected-times {
    list-style-type: none;
    padding: 0;
  }

  .otp-input-wrapper {
    width: 240px;
    text-align: left;
    display: inline-block;
  }
  .otp-input-wrapper input {
    padding: 0;
    width: 264px;
    font-size: 22px;
    font-weight: 600;
    color: #3e3e3e;
    background-color: transparent;
    border: 0;
    margin-left: 2px;
    letter-spacing: 30px;
    font-family: sans-serif !important;
  }
  .otp-input-wrapper input:focus {
    box-shadow: none;
    outline: none;
  }
  .otp-input-wrapper svg {
    position: relative;
    display: block;
    width: 240px;
    height: 2px;
  }
  
  

  /**
   * Confirmation section
   */
  .success-confirmation-section {
    display: none;
    margin-top: 20px;
  }

  .selected-day {
    background-color: var( --cp-color, 'dodgerblue' );
  }
  button {
    background-color: var( --cp-color, 'dodgerblue' );
  }
  button:hover {
    background-color: transparent;
    border-color: var( --cp-color, 'dodgerblue' );
    color: var( --cp-color, 'dodgerblue' );
  }
  
 `;
window.campaignStyles = campaignStyles;