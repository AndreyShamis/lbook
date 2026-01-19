<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookCycleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('setup')
            //->add('passRate')
            //->add('timeStart')
            //->add('timeEnd')
            //->add('period')
            //->add('testsTimeSum')
            ->add('build')
            ->add('disabled')
            ->add('forDelete')
            ->add('keepForever')
            ->add('deleteAt', TextType::class, array(
                'attr' => array(
                    'class' => 'form-control deleteAt-date-timepicker'
                ),
            ))
        ;

        $nulTransformer = new CallbackTransformer(
            function (\DateTime $input)
            {
                return $input->format('Y/m/d H:i:s');
            },
            function ($input)
            {
                /**
                 * Convert back input from User into Cycle object DateTime
                 */
                return \DateTime::createFromFormat('Y/m/d H:i:s', $input);
            }
        );
        $builder->get('deleteAt')->addModelTransformer($nulTransformer);

    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => LogBookCycleForm::class,
        ]);
    }
}
