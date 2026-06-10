<x-mail.layout>

<div style="font-size: 26px; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; line-height: 1.15; color: #ffffff; margin-bottom: 4px;">
  {{ $subjectText }}
</div>

<p style="font-size: 13.5px; color: #aaaaaa; line-height: 1.6; margin-top: 14px; margin-bottom: 24px;">
  {{ __('Hello,') }} {{ $member->name }}<br><br>
  {!! nl2br(e($body)) !!}
</p>

</x-mail.layout>
