<x-mail::message>
# Introduction

The body of your message.
your order #{{$order->id}} has been placed.
<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
